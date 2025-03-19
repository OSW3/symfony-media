<?php 
namespace OSW3\Media\Manager;

use OSW3\Media\Utils\StringUtils;
use OSW3\Media\Manager\ProcessManager;
use OSW3\Media\Manager\StorageManager;
use OSW3\Media\Manager\ProviderManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class MediaManager
{
    public function __construct(
        #[Autowire(service: 'service_container')] 
        private ContainerInterface $container,
        private Filesystem $filesystem,
        private EntityManager $entityManager,
        private ProviderManager $providerManager,
        private StorageManager $storageManager,
        private ProcessManager $processManager,
    ){}

    private function source(UploadedFile $file): object
    {
        $filename  = $file->getClientOriginalName();
        $basename  = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $mimetype  = $file->getClientMimeType();
        $extension = $file->getClientOriginalExtension();
        $size      = $file->getSize();
        $md5       = file_exists($file->getPathname()) ? md5_file($file->getPathname()) : null;
        $type      = $this->extractMimeType($file->getClientMimeType());

        return (object) [
            'filename'  => $filename,
            'basename'  => $basename,
            'mimetype'  => $mimetype,
            'extension' => $extension,
            'size'      => $size,
            'md5'       => $md5,
            'type'      => $type,
        ];
    }

    private function temp(UploadedFile $file): object 
    {
        $path     = $file->getPath();
        $pathname = $file->getPathname();
        $filename = $file->getFilename();
        $basename = $file->getBasename();

        return (object) [
            'path'     => $path,
            'pathname' => $pathname,
            'filename' => $filename,
            'basename' => $basename,
        ];
    }

    private function provider(string $name): object 
    {
        $provider = $this->providerManager->get($name);

        $entity                 = $provider['entity'];
        $allowDelete            = $provider['allow_delete'];
        $allowUpdate            = $provider['allow_update'];
        $filenameStrategy       = $provider['filename']['strategy'];
        $filenamePrefix         = $provider['filename']['prefix'];
        $filenameSuffix         = $provider['filename']['suffix'];
        $filenameDatetimeFormat = $provider['filename']['datetimeFormat'];
        $filenameLength         = $provider['filename']['length'];
        $temp                   = $provider['temp'];

        return (object) [
            'name'                   => $name,
            'entity'                 => $entity,
            'allowDelete'            => $allowDelete,
            'allowUpdate'            => $allowUpdate,
            'filenameStrategy'       => $filenameStrategy,
            'filenamePrefix'         => $filenamePrefix,
            'filenameSuffix'         => $filenameSuffix,
            'filenameDatetimeFormat' => $filenameDatetimeFormat,
            'filenameLength'         => $filenameLength,
            'temp'                   => $temp,
        ];
    }

    private function aliases(object $source, object $provider): array 
    {
        $basename = match($provider->filenameStrategy) {
            'datetime' => date($provider->filenameDatetimeFormat),
            'md5'      => $source->md5,
            'random'   => StringUtils::random($provider->filenameLength),
            'unique'   => uniqid(),
            default    => $source->basename,
        };
        $filename = "{$basename}.{$source->extension}";

        return ['original' => $filename];
    }

    private function basename(object $source, object $provider): string 
    {
        return match($provider->filenameStrategy) {
            'datetime' => date($provider->filenameDatetimeFormat),
            'md5'      => $source->md5,
            'random'   => StringUtils::random($provider->filenameLength),
            'unique'   => uniqid(),
            default    => $source->basename,
        };
    }

    private function presets(string $name, object $source): array 
    {
        $provider = $this->providerManager->get($name);
        $presets  = $provider['presets'];

        return $presets;
    }

    private function storages(string $name): array 
    {
        $provider = $this->providerManager->get($name);
        $storages = $provider['storages'];
        
        return $storages;
    }

    // public function upload(Form $form, string $widget, string $providerName): ?object
    public function upload(UploadedFile $file, string $providerName): ?object
    {
        // Exit if the provider is not defined
        if (!$this->providerManager->has($providerName)) {
            return null;
        }
        
        $source            = $this->source($file);
        $temp              = $this->temp($file);
        $provider          = $this->provider($providerName);
        $basename          = $this->basename($source, $provider);
        $aliases           = $this->aliases($source, $provider);
        $presets           = $this->presets($providerName, $source);
        $storages          = $this->storages($providerName);

        $media             = [];
        $media['source']   = $source;
        $media['basename'] = $basename;
        $media['temp']     = $temp;
        $media['provider'] = $provider;
        $media['aliases']  = $aliases;
        $media['presets']  = $presets;
        $media['storages'] = $storages;



        // Process
        // --

        $this->processManager
            ->prepare($media) // Move upload to temp directory
            ->execute() // Apply presets
        ;
        
        // Update aliases list
        $media['aliases'] = array_merge($media['aliases'], $this->processManager->getAliases());
        

        

        // Storages
        // --

        // $this->storageManager
        //     ->prepare($media)
        //     ->execute()
        // ;
        // dump($media);
        $this->storageManager->copy($media);
        // dd($media);

        // Clear Temp directory
        $this->clearDirectory($provider->temp);

        // dd($media);





        // dd('"""');
        // Save Media (entity)
        // --

        return $this->entityManager->save( $media );
    }


    private function extractMimeType(string $mimeType): ?string 
    {
        $parts = explode('/', $mimeType, 2);
        return $parts[0] ?? null;
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