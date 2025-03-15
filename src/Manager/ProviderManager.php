<?php 
namespace OSW3\Media\Manager;

use OSW3\Media\DependencyInjection\Configuration;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class ProviderManager 
{
    private array $providers;

    public function __construct(
        #[Autowire(service: 'service_container')] 
        private ContainerInterface $container,
    ){
        $this->providers = $container->getParameter(Configuration::NAME)['providers'];
    }

    public function getAll(): array
    {
        return $this->providers;
    }

    public function has(string $provider): bool
    {
        return isset($this->providers[$provider]);
    }

    public function get(string $provider): array
    {
        if (!isset($this->providers[$provider])) {
            throw new \InvalidArgumentException(sprintf('The provider "%s" does not exist.', $provider));
        }

        return $this->providers[$provider];
    }
}