<?php
namespace OSW3\Media\Service;

use OSW3\Media\DependencyInjection\Configuration;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MediaService 
{
    private array $params;

    public function __construct(
        #[Autowire(service: 'service_container')] 
        private ContainerInterface $container,
    ){
        $this->params = $container->getParameter(Configuration::NAME);
    }

    public function create(UploadedFile $data, string $provider) {

        dump($this->params['providers'][$provider]);
        dump($data);
        dd($provider);
    }

}