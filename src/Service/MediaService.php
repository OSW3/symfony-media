<?php
namespace OSW3\Media\Service;

use OSW3\Media\Enum\Storage\Type;
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
        $alias = $aliases[$alias] ?? "original";

        switch ($storage['type'])
        {
            case Type::DROPBOX->value: return $alias; break;

            default: return file_exists(Path::join( $storage['destination'], $alias ))
                ? Path::join( $storage['public'], $alias )
                : Path::join( "/", $storage['defaults']['image'] )
            ;
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
                $storage['type'] !== Type::DROPBOX->value
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
