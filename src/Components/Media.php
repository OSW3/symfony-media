<?php 
namespace OSW3\Media\Components;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\UX\TwigComponent\Attribute\PreMount;
use OSW3\Media\DependencyInjection\Configuration;
use OSW3\Media\Enum\Storage\Type;
use OSW3\Media\Manager\ProviderManager;
use OSW3\Media\Manager\StorageManager;
use Symfony\UX\TwigComponent\Attribute\PostMount;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Path;

#[AsTwigComponent(template: '@Media/media/base.twig')]
class Media 
{
    private array $config;

    #[ExposeInTemplate(getter: 'doNotExpose')]
    public ?object $media;

    #[ExposeInTemplate(getter: 'doNotExpose')]
    public string $storage;

    #[ExposeInTemplate(getter: 'doNotExpose')]
    public string $alias;

    #[ExposeInTemplate(name: 'src', getter: 'fetchSrc')]
    public string $src;

    public function __construct(
        #[Autowire(service: 'service_container')] private ContainerInterface $container,
        private ManagerRegistry $managerRegistry,
        private ProviderManager $providerManager,
        private StorageManager $storageManager,
    ){
        $this->config = $container->getParameter(Configuration::NAME);
    }

    #[PreMount]
    public function preMount(array $data): array
    {
        $resolver = new OptionsResolver();
        $resolver->setIgnoreUndefined(false);

        $resolver->setRequired('media');
        $resolver->setAllowedTypes('media', ['object']);

        $resolver->setRequired('storage');
        $resolver->setAllowedTypes('storage', ['string']);

        $resolver->setRequired('alias');
        $resolver->setAllowedTypes('alias', ['string']);

        return $resolver->resolve($data) + $data;
    }

    // #[PostMount]
    public function fetchSrc()
    {
        // Retrieve the provider
        // $provider = $this->media->getMediaProvider();
        // $provider = $this->providerManager->get($provider);
        
        // Retrieve storages list
        // $storages = $provider['storages'];

        // Retrieve the storage
        $storage = $this->storage;
        $storage = $this->storageManager->get($storage);

        $alias = $this->alias;
        $aliases = $this->media->getMediaAliases();

        $path = Path::join($storage['public'], $aliases[$alias]);

        return $path;

        // dump( $path );
        // dump( $storage );
        // dd( $this->media );

        // if (!in_array($this->storage, $storages)) {
        //     // todo: storage not found -> return place holder media
        //     dd("storage not found -> return place holder media");
        // }

        // $storage = $this->storageManager->get($this->storage);
        // $publicPath = $storage['publicPath'];

        // // match (Type::from($storage['type'])) {
        // //     Type::DROPBOX => 
        // //     default => true
        // // };

        // // dump( $provider );
        // dump( $storage );
        // dump( $publicPath );
        // // dump( $this->storage );

        // dd($this->media);
    }

    public function doNotExpose(): null {
        return null;
    }
}