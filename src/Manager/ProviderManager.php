<?php 
namespace OSW3\Media\Manager;

use OSW3\Media\DependencyInjection\Configuration;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class ProviderManager 
{
    private array $providers;

    public function __construct(
        #[Autowire(service: 'service_container')] private ContainerInterface $container,
    ){
        $config = $container->getParameter(Configuration::NAME);
        $this->providers = $config['providers'];
    }

    public function getAll(): array
    {
        return $this->providers;
    }

    public function get(string $provider): array
    {
        if (!isset($this->providers[$provider])) {
            throw new \InvalidArgumentException(sprintf('The provider "%s" does not exist.', $provider));
        }

        return $this->providers[$provider];
    }
}