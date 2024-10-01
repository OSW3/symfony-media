<?php 
namespace OSW3\Media\EventListener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use OSW3\Media\Manager\ProviderManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MediaAliasesListener
{
    public function __construct(
        private ProviderManager $providerManager
    ){}

    public function postLoad(LifecycleEventArgs $args)
    {
        $providers = $this->providerManager->getAll();
        $entities = array_values(array_map(fn($provider) => $provider['entity']['class'], $providers));
        $entity = $args->getObject();

        if (!in_array(get_class($entity), $entities, true)) {
            return;
        }

        $aliases = $entity->getMediaAliases();
        $aliases[] = [
            'name' => "original",
            'width' => null,
            'height' => null,
            'filename' => $entity->getMediaFilename(),
      
        ];

        $entity->setMediaAliases($aliases);
    }
}