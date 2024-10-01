<?php 
namespace OSW3\Media\Manager;

use Symfony\Component\Form\Form;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class MediaManager
{
    public function __construct(
        #[Autowire(service: 'service_container')] private ContainerInterface $container,
        private Filesystem $filesystem,
        private EntityManager $entityManager,
        private ProviderManager $providerManager,
        private StorageManager $storageManager,
        private ProcessManager $processManager,
    ){}


    public function upload(Form $form, string $widget, string $provider): ?object
    {
        // Exit if widget is not submitted or is null
        if (!isset($form[$widget]) || $form[$widget]->getData() === null) {
            return null;
        }

        
        $media = [];


        // Retrieve config & data
        // --

        // Retrieve uploaded file
        $file           = $form[$widget]->getData();

        // Retrieve bundle config
        $provider_options = $this->providerManager->get($provider);
        $nameStrategy     = $provider_options['nameStrategy'];
        $datetimeFormat   = $provider_options['datetimeFormat'];
        $storages         = $provider_options['storages'];
        $processes        = $provider_options['processes'];
        $tempPath         = $provider_options['tempPath'];
        
        // Replace Storage & Processes reference with their config
        array_walk($storages, fn(&$storage) => $storage = $this->storageManager->get($storage));
        array_walk($processes, fn(&$process) => $process = $this->processManager->get($process));

        $media['provider'] = $provider;
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
        $media['processes'] = $this->processManager->prepare($processes, $source_type, $media);

        // Build aliases array
        $media['aliases'] = $this->processManager->aliases($processes, $source_type, $media);
        $media['aliases'] = array_filter($media['aliases'], fn($alias) => !!$alias);

        // Execute processes
        $this->processManager->execute($media['processes'], $source_type);


        // Storages
        // --
        
        // Prepare storages
        $media['storages'] = $this->storageManager->prepare($storages, $media);

        // Execute storages
        $this->storageManager->execute($media['storages']);


        // Clear Temp directory
        $this->clearDirectory($tempPath);



        // Save Media (entity)
        // --

        return $this->entityManager->save($provider, $media);
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