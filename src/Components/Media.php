<?php 
namespace OSW3\Media\Components;

use Symfony\UX\TwigComponent\Attribute\PreMount;
use OSW3\Media\Service\MediaService;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent(template: '@Media/base.twig')]
class Media 
{
    #[ExposeInTemplate(getter: 'doNotExpose')]
    public bool $absolute;

    #[ExposeInTemplate(getter: 'doNotExpose')]
    public object $media;

    #[ExposeInTemplate(getter: 'doNotExpose')]
    public string $storage;

    #[ExposeInTemplate(getter: 'doNotExpose')]
    public string $alias;

    #[ExposeInTemplate(name: 'src', getter: 'fetchSrc')]
    public string $src;

    public function __construct(
        private MediaService $mediaService,
    ){}

    #[PreMount]
    public function preMount(array $data): array
    {
        $resolver = new OptionsResolver();
        $resolver->setIgnoreUndefined(false);

        $resolver->setDefault('absolute', false);
        $resolver->setAllowedTypes('absolute', ['bool']);

        $resolver->setRequired('media');
        $resolver->setAllowedTypes('media', ['object']);

        $resolver->setRequired('storage');
        $resolver->setAllowedTypes('storage', ['string']);

        $resolver->setRequired('alias');
        $resolver->setAllowedTypes('alias', ['string']);

        return $resolver->resolve($data) + $data;
    }

    public function fetchSrc()
    {
        $options = [
            'media'   => $this->media,
            'storage' => $this->storage,
            'alias'   => $this->alias,
        ];

        return $this->absolute 
            ? $this->mediaService->url($options)
            : $this->mediaService->path($options)
        ;
    }

    public function doNotExpose(): null {
        return null;
    }
}