<?php
namespace OSW3\Media\Service;

use OSW3\Media\Enum\File\Type as FileType;
use OSW3\Media\Enum\Storage\Type as StorageType;
use OSW3\Media\Manager\StorageManager;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\RequestStack;

class MediaService 
{
    public function __construct(
        private RequestStack $requestStack,
        private StorageManager $storageManager,
    ){}

    public function path(array $options): string 
    {
        $media = $options['media'];

        $storage = $options['storage'];
        $storage = $this->storageManager->get($storage);

        if ($media === null) {
            return Path::join( "/", $storage['defaults']['image'] );
        }
        
        $aliases = $media->getMediaAliases();
        $alias = $options['alias'];
        $alias = $aliases[$alias] ?? null;

        switch ($storage['type'])
        {
            case StorageType::DROPBOX->value: return $alias; break;

            default: 

                // Define the filetype
                // --

                $mimeType = $media->getOriginalMimetype();
                $fileType = "image";

                if (preg_match('/^([^\/]+)/', $mimeType, $matches)) {
                    $fileType = $matches[0];
                }

                if (!in_array($fileType, FileType::toArray())) {
                    $fileType = "image";
                }


                // Find the Alias
                // --

                $fileExists = false;
                $isFile = false;

                if ($alias) {
                    $fileExists = file_exists(Path::join( $storage['destination'], $alias ));
                    $isFile = is_file(Path::join( $storage['destination'], $alias ));
                }


                // File exists
                // --

                if ($fileExists && $isFile) {
                    return Path::join( $storage['public'], $alias );
                }


                $fallbacks = $storage['fallbacks'][$fileType];

                foreach ($fallbacks as $fallback) {
                    if ($fallback !== 'default') {
                        $alias = $aliases[$fallback] ?? null;

                        if ($alias == null) {
                            continue;
                        }
                        
                        $fileExists = file_exists(Path::join( $storage['destination'], $alias ));
                        $isFile = is_file(Path::join( $storage['destination'], $alias ));
            
                        if (!$fileExists || !$isFile) {
                            continue;
                        }

                        return Path::join( $storage['public'], $alias );
                    }
                }

                return Path::join( "/", $storage['defaults'][$fileType] );

        }
    }

    public function url(array $options): string 
    {
        $request = $this->requestStack->getCurrentRequest();
        $path    = $this->path($options);

        if (str_starts_with($path, "http")) {
            return $path;
        }

        return Path::join( $request->getSchemeAndHttpHost(), $path );
    }

    public function set(array $options, bool $absolute=false): array 
    {
        $media   = $options['media'] ?? null;
        $set     = $options['set'] ?? [];
        $storage = $options['storage'] ?? '';
        $storage = $this->storageManager->get($storage);
        
        if (!$media || empty($set)) {
            return [];
        }
        
        $aliases = $media->getMediaAliases() ?? [];
        $srcset  = [];
        $sizes   = [];
        
        $set = array_filter($set, fn($entry) => isset($aliases[$entry[0]]));
        $lastKey = array_key_last($set);


        foreach ($set as $key => $entry) 
        {
            [$alias, $width, $size] = array_pad($entry, 3, null);

            if (
                !file_exists(Path::join( $storage['destination'], $aliases[$alias] )) &&
                $storage['type'] !== StorageType::DROPBOX->value
            ) continue;


            $filename = $absolute 
                ? $this->url([
                    'media'   => $media,
                    'storage' => $options['storage'],
                    'alias'   => $alias,
                ]) : $this->path([
                    'media'   => $media,
                    'storage' => $options['storage'],
                    'alias'   => $alias,
                ])
            ;


            $srcset[] = sprintf('%s %s', $filename, $width);

            if (!$size) {
                continue;
            }

            if ($key === $lastKey) {
                $sizes[] = $size;
            } else {
                $sizes[] = sprintf('(max-width: %s) %s', $width, $size);
            }
        }

        return [
            'srcset' => implode(', ', $srcset),
            'sizes'  => implode(', ', $sizes)
        ];
    }
}
