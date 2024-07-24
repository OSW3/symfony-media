<?php 
namespace OSW3\Media;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use OSW3\Media\DependencyInjection\Configuration;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class MediaBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        (new Configuration)->generateProjectConfig($container->getParameter('kernel.project_dir'));
    }
}