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

#[AsTwigComponent(template: '@Media/media/base.twig')]
class Media 
{
    private array $config;

    #[ExposeInTemplate(getter: 'checkEntity')]
    public $media;

    #[ExposeInTemplate(getter: 'doNotExpose')]
    public string $storage;

    #[ExposeInTemplate(getter: 'doNotExpose')]
    public ?string $size;

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
        $resolver->setIgnoreUndefined(true);

        $resolver->setDefault('storage', null);
        $resolver->setAllowedTypes('storage', ['string','null']);

        $resolver->setDefault('size', null);
        $resolver->setAllowedTypes('size', ['string','null']);

        return $resolver->resolve($data) + $data;
    }

    #[PostMount]
    public function postMount()
    {
        $provider = $this->media->getMediaProvider();
        $provider = $this->providerManager->get($provider);
        $storages = $provider['storages'];

        if (!in_array($this->storage, $storages)) {
            // todo: storage not found -> return place holder media
            dd("storage not found -> return place holder media");
        }

        $storage = $this->storageManager->get($this->storage);
        $publicPath = $storage['publicPath'];

        // match (Type::from($storage['type'])) {
        //     Type::DROPBOX => 
        //     default => true
        // };

        // dump( $provider );
        dump( $storage );
        dump( $publicPath );
        // dump( $this->storage );

        dd($this->media);
    }

    public function doNotExpose(): null {
        return null;
    }

    /**
     * Check if Entity exist
     *
     * @return void
     */
    public function checkEntity(): null
    {
        $entity       = $this->media;
        $metaData     = $this->managerRegistry->getManager()->getMetadataFactory()->getAllMetadata();
        $isRegistered = !!array_filter($metaData, fn($meta) => get_debug_type($entity) === $meta->getName());
        
        if (!$isRegistered) {
            // Todo: emit new Exception
            throw new \Exception('Invalid entity');
        }

        return $this->doNotExpose();
    }
}