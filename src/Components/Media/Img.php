<?php 
namespace OSW3\Media\Components\Media;

use Symfony\UX\TwigComponent\Attribute\PreMount;
use OSW3\Media\Service\MediaService;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent(template: '@Media/img.twig')]
class Img 
{
    public bool $absolute;
    public string $alias;
    public object $media;
    public string $storage;

    #[ExposeInTemplate(name: 'id', getter: 'fetchId')]
    public ?string $id;

    #[ExposeInTemplate(name: 'class', getter: 'fetchClass')]
    public ?string $class;

    #[ExposeInTemplate(name: 'rel', getter: 'fetchRel')]
    public ?string $rel;

    #[ExposeInTemplate(name: 'style', getter: 'fetchStyle')]
    public ?string $style;

    #[ExposeInTemplate(name: 'alt', getter: 'fetchAlt')]
    public ?string $alt;

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

        $resolver->setDefault('id', null);
        $resolver->setAllowedTypes('id', ['string', 'null']);

        $resolver->setDefault('class', null);
        $resolver->setAllowedTypes('class', ['string', 'null']);

        $resolver->setDefault('rel', null);
        $resolver->setAllowedTypes('rel', ['string', 'null']);

        $resolver->setDefault('style', null);
        $resolver->setAllowedTypes('style', ['string', 'null']);

        $resolver->setDefault('alt', null);
        $resolver->setAllowedTypes('alt', ['string', 'null']);

        $resolver->setDefault('alias', 'original');
        $resolver->setAllowedTypes('alias', ['string']);

        $resolver->setRequired('media');
        $resolver->setAllowedTypes('media', ['object']);

        $resolver->setRequired('storage');
        $resolver->setAllowedTypes('storage', ['string']);

        return $resolver->resolve($data) + $data;
    }

    public function fetchId(): string
    {
        return trim($this->id);
    }

    public function fetchClass(): string
    {
        return trim($this->class);
    }

    public function fetchRel(): string
    {
        return trim($this->rel);
    }

    public function fetchStyle(): string
    {
        return trim($this->style);
    }

    public function fetchAlt(): string
    {
        return trim($this->alt);
    }

    public function fetchSrc(): string
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
}