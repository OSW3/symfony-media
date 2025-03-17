<?php
namespace OSW3\Media\Service;

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

        $alias = $options['alias'];
        $aliases = $media->getMediaAliases();

        return Path::join( $storage['public'], $aliases[$alias] );
    }

    public function url(array $options): string 
    {
        $request = $this->requestStack->getCurrentRequest();

        return Path::join( $request->getSchemeAndHttpHost(), $this->path($options) );
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
