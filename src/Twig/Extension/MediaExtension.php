<?php 
namespace OSW3\Media\Twig\Extension;


use Twig\TwigFunction;
use Twig\Extension\AbstractExtension;
use OSW3\Media\Twig\Runtime\MediaExtensionRuntime;

class MediaExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('media_path', [MediaExtensionRuntime::class, 'path']),
            new TwigFunction('media_url', [MediaExtensionRuntime::class, 'url']),
            new TwigFunction('media_set', [MediaExtensionRuntime::class, 'set']),
        ];
    }
}
