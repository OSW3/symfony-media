<?php 
namespace OSW3\Media\Twig\Runtime;

use OSW3\Media\Service\MediaService;
use Twig\Extension\RuntimeExtensionInterface;

class MediaExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private MediaService $mediaService,
    ){}

    public function path(array $options): string 
    {
        return $this->mediaService->path($options);
    }

    public function url(array $options): string 
    {
        return $this->mediaService->url($options);
    }

    public function set(array $options, bool $absolute=false): array 
    {
        return $this->mediaService->set($options, $absolute);
    }
}