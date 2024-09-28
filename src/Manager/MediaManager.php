<?php 
namespace OSW3\Media\Manager;

use OSW3\CloudManager\Client;
use OSW3\Media\Enum\File\Type;
use Symfony\Component\Form\Form;
use OSW3\Media\Processor\PdfProcessor;
use Symfony\Component\Filesystem\Path;
use OSW3\Media\Processor\AudioProcessor;
use OSW3\Media\Processor\ImageProcessor;
use OSW3\Media\Processor\VideoProcessor;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\Request;
use OSW3\Media\Enum\Storage\Type as StorageType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

final class MediaManager
{
    private array $storages;
    private array $processes;
    private array $providers;

    public function __construct(
        #[Autowire(service: 'service_container')] private ContainerInterface $container,
        private Filesystem $filesystem,
        private AudioProcessor $audiProcessor,
        private ImageProcessor $imageProcessor,
        private PdfProcessor $pdfProcessor,
        private VideoProcessor $videoProcessor,
    )
    {
        $config = $container->getParameter('media');
        $this->storages = $config['storages'];
        $this->processes = $config['processing'];
        $this->providers = $config['providers'];
    }


    public function upload(Form $form, string $widget, string $provider) 
    {
        // Exit if widget is not submitted or is null
        if (!isset($form[$widget]) || $form[$widget]->getData() === null) {
            return null;
        }

        // Exit if provider don' exists
        if (!isset($this->providers[$provider])) {
            throw new \InvalidArgumentException(sprintf('The provider "%s" does not exist.', $provider));
        }

        $media = [];


        // Retrieve config & data
        // --

        // Retrieve uploaded file
        $file           = $form[$widget]->getData();

        // Retrieve bundle config
        $provider_name    = $provider;
        $provider_options = $this->providers[$provider];
        $nameStrategy     = $provider_options['nameStrategy'];
        $datetimeFormat   = $provider_options['datetimeFormat'];
        $provider_storages = $provider_options['storages'];
        $processes        = $provider_options['processes'];
        $tempPath         = $provider_options['tempPath'];
        
        // Replace Storage & Processes reference with their config
        array_walk($provider_storages, fn(&$name) => $name = $this->storages[$name]);
        array_walk($processes, fn(&$name) => $name = $this->processes[$name]);

        $media['provider'] = $provider_name;
        $media['tempPath'] = $tempPath;


        // Parse the uploaded file
        // --

        // Original source file
        $source_filename  = $file->getClientOriginalName();
        $source_basename  = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $source_mimetype  = $file->getClientMimeType();
        $source_extension = $file->getClientOriginalExtension();
        $source_size      = $file->getSize();
        $source_md5       = file_exists($file->getPathname()) ? md5_file($file->getPathname()) : null;
        $source_type      = $this->extractMimeType($file->getClientMimeType());

        $media['source']              = [];
        $media['source']['filename']  = $source_filename;
        $media['source']['basename']  = $source_basename;
        $media['source']['mimetype']  = $source_mimetype;
        $media['source']['extension'] = $source_extension;
        $media['source']['size']      = $source_size;
        $media['source']['md5']       = $source_md5;
        $media['source']['type']      = $source_type;

        
        // Uploaded file
        $file_path     = $file->getPath();
        $file_pathname = $file->getPathname();
        $file_filename = $file->getFilename();
        $file_basename = $file->getBasename();

        $media['file']             = [];
        $media['file']['path']     = $file_path;
        $media['file']['pathname'] = $file_pathname;
        $media['file']['filename'] = $file_filename;
        $media['file']['basename'] = $file_basename;



        // Media names
        // --

        $media_mimetype  = $source_mimetype;
        $media_extension = $source_extension;
        $media_basename  = $this->generateMediaBasename(
            strategy      : $nameStrategy,
            md5           : $source_md5,
            original      : $source_basename,
            datetimeFormat: $datetimeFormat
        );
        $media_filename  = "{$media_basename}.{$media_extension}";

        $media['media']              = [];
        $media['media']['basename']  = $media_basename;
        $media['media']['filename']  = $media_filename;
        $media['media']['mimetype']  = $media_mimetype;
        $media['media']['extension'] = $media_extension;


        // Process
        // --

        // Find process by file type
        $processes = array_merge(...$processes);
        $processes = array_filter($processes, fn($process) => !!array_intersect([
            $source_type, 
            $source_mimetype
        ], $process['filetype']));

        // Prepare processes (add process to $media)
        $media['processes'] = array_map(fn($process) => match (Type::from($source_type)) {
            Type::AUDIO => $this->audiProcessor->prepare($process, $media),
            Type::IMAGE => $this->imageProcessor->prepare($process, $media),
            Type::PDF   => $this->pdfProcessor->prepare($process, $media),
            Type::VIDEO => $this->videoProcessor->prepare($process, $media),
            default     => null
        }, $processes);

        // Build aliases array
        $media['aliases'] = array_map(fn($process) => match (Type::from($source_type)) {
            Type::AUDIO => $this->audiProcessor->getAlias($process, $media),
            Type::IMAGE => $this->imageProcessor->getAlias($process, $media),
            Type::PDF   => $this->pdfProcessor->getAlias($process, $media),
            Type::VIDEO => $this->videoProcessor->getAlias($process, $media),
            default     => null
        }, $processes);
        $media['aliases'] = array_filter($media['aliases'], fn($alias) => !!$alias);



        // Storages
        // --
        
        $media['storages'] = array_map(function($storage) use ($media) {

            if ($storage['type'] === StorageType::LOCAL->value) {
                $targetPath = Path::join($this->container->get('kernel')->getProjectDir(), $storage['targetPath']);
                $storage['targetPath'] = $targetPath;

            }

            $storage['files'] = [];

            $storage['files']['original'] = [
                'source' => $media['file']['pathname'],
                'target' => Path::join($storage['targetPath'], $media['media']['filename'])
            ];

            foreach ($media['aliases'] as $alias) {
                $storage['files'][$alias['name']] = [
                    'source' => Path::join($media['tempPath'], $alias['filename']),
                    'target' => Path::join($storage['targetPath'], $alias['filename'])
                ];
            }

            return $storage;

        } , $provider_storages);



        // Execute processes
        // --

        array_walk($media['processes'], fn($process) => match (Type::from($source_type)) {
            Type::AUDIO => $this->audiProcessor->execute($process),
            Type::IMAGE => $this->imageProcessor->execute($process),
            Type::PDF   => $this->pdfProcessor->execute($process),
            Type::VIDEO => $this->videoProcessor->execute($process),
            default     => null
        });



        // Execute storages
        // --

        $clients = [];

        array_walk($media['storages'], function($storage) use (&$clients) {
            match (StorageType::from($storage['type'])) {
                
                StorageType::DROPBOX => array_walk($storage['files'], function($entry) use (&$clients, $storage) {
                    if (!isset($clients[StorageType::DROPBOX->value])) {
                        $clients[StorageType::DROPBOX->value] = new Client("dropbox:token://{$storage['token']}");
                    }
                    $clients[StorageType::DROPBOX->value]->uploadFile($entry['source'], $entry['target']);
                }),
                
                StorageType::FTP => array_walk($storage['files'], function($entry) use (&$clients, $storage) {
                    if (!isset($clients[StorageType::FTP->value])) {
                        $dsn    = "ftp://{$storage['dsn']}";

                        $clients[StorageType::FTP->value] = new Client($dsn);
                    }
                    $clients[StorageType::FTP->value]->uploadFile($entry['source'], $entry['target']);
                }),

                StorageType::LOCAL => array_walk($storage['files'], function($entry) {
                    $this->filesystem->copy($entry['source'], $entry['target']);
                }),

                default => null
            };
        });

        // Clear Temp directory
        $this->clearDirectory($tempPath);



        // Save Media (entity)
        // --


        return $media;
    }

    private function random($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
    
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
    
        return $randomString;
    }

    private function extractMimeType(string $mimeType): ?string 
    {
        $parts = explode('/', $mimeType, 2);
        return $parts[0] ?? null;
    }
    
    private function generateMediaBasename(string $original, string $strategy, string $md5, string $datetimeFormat): string
    {
        return match($strategy) {
            'datetime' => date($datetimeFormat),
            'md5'      => $md5,
            'random'   => $this->random(),
            'uniqid'   => uniqid(),
            default    => $original,
        };
    }

    private function clearDirectory(string $directoryPath)
    {
        if (is_dir($directoryPath)) {
            $files = scandir($directoryPath);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    $this->filesystem->remove($directoryPath . '/' . $file);
                }
            }
        }
    }
}