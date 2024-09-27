<?php 
namespace OSW3\Media\Manager;

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
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class MediaManager
{
    // private Request $request;
    // private FileBag $files;
    // private array $config;
    private array $storages;
    private array $processes;
    private array $providers;

    public function __construct(
        #[Autowire(service: 'service_container')] private ContainerInterface $container,
        private Filesystem $filesystem,
        private AudioProcessor $audiAudioProcessor,
        private ImageProcessor $imageProcessor,
        private PdfProcessor $pdPdfProcessor,
        private VideoProcessor $videVideoProcessor,
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



        // Retrieve config & data
        // --

        // Retrieve uploaded file
        $file           = $form[$widget]->getData();

        // Retrieve bundle config
        $provider       = $this->providers[$provider];
        $nameStrategy   = $provider['nameStrategy'];
        $datetimeFormat = $provider['datetimeFormat'];
        $tempPath       = $provider['tempPath'];
        $storages       = $provider['storages'];
        $processes      = $provider['processes'];

        // Replace Storage & Processes reference with their config
        array_walk($storages, fn(&$name) => $name = $this->storages[$name]);
        array_walk($processes, fn(&$name) => $name = $this->processes[$name]);



        // Parse the uploaded file
        // --

        $file_name      = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $file_mimetype  = $file->getClientMimeType();
        [$file_type]    = explode("/", $file_mimetype);
        $file_extension = $file->getClientOriginalExtension();
        $file_size      = $file->getSize();
        $file_md5       = file_exists($file->getPathname()) ? md5_file($file->getPathname()) : null;


        // Media names
        // --

        // Generate the safe name (media name)
        $media_name = match($nameStrategy) {
            'datetime' => date($datetimeFormat),
            'md5'      => $file_md5,
            'random'   => $this->random(),
            'uniqid'   => uniqid(),
            default    => $file_name,
        };

        $media_extension = $file_extension;
        $media_mimetype = $file_mimetype;


        // Temp File
        // --

        // Temp filename
        $temp_filename = Path::join($tempPath, $media_name);

        // Create temp dir
        $this->filesystem->mkdir($tempPath);

        // Move uploaded file to temp directory
        copy($file->getPathname(), $temp_filename);



        // Process
        // --

        // Find process by file type
        $processes = array_merge(...$processes);
        $processes = array_filter($processes, fn($process) => !!array_intersect([
            $file_type, 
            $file_mimetype
        ], $process['filetype']));


        foreach ($processes as $processKey => $process) {
            
            // Generate Media Filename
            $media_filename = $this->generateMediaFilename($media_name, $media_extension, $process);
            // dump($media_filename);

            // $media = [
            //     'filename' => $media_filename,
            //     'extension' => $media_extension,
            //     'mimetype' => $media_mimetype,
            // ];

            // Process the file
            // match (Type::from($file_type)) {
            //     Type::AUDIO => $this->audiAudioProcessor->execute($process, $file),
            //     Type::IMAGE => $this->imageProcessor->execute($process, $file),
            //     Type::PDF   => $this->pdPdfProcessor->execute($process, $file),
            //     Type::VIDEO => $this->videVideoProcessor->execute($process, $file),
            //     default     => null
            // };



            // $media = [
            //     'filename' => $media_filename,
            //     'extension' => $media_extension,
            //     'mimetype' => $media_mimetype,
            // ];

            // foreach ($storages as $storageKey => $storage) {
            //     $target = Path::join($storage['targetPath'], $media['filename']);
            //     $storages[$storageKey] = array_merge($storages[$storageKey], [
            //         'media' => array_merge($media, [
            //             'target' => $target
            //         ]),
            //     ]);
            // }

            // $processes[$processKey] = array_merge($processes[$processKey], [
            //     'source' => $file->getPathname(),
            //     'media' => $media,
            //     'storages' => $storages
            // ]);
        }

        // foreach ($processes as $key => $process) {
        //     dump($process);
        // }


        // dump($provider);
        // dump($file_mimetype);
        // dump($file_type);
        // dump($file_md5);
        // dump($file_extension);
        // dump($file_size);
        // dump($media_name);
        // dump($processes);
        // dump($filteredData);

        unlink($temp_filename);
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

    private function generateMediaFilename(string $name, string $extension, array $process): string
    {
        $filename = $name;

        if (isset($process['options']['alias'])) {
            $filename .= "-{$process['options']['alias']}";
        }

        return "{$filename}.{$extension}";
    }
}