<?php 
namespace OSW3\Media;

use OSW3\Media\Utils\ConfigurationYaml;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class MediaBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        ConfigurationYaml::write($container->getParameter('kernel.project_dir'));
    }
}