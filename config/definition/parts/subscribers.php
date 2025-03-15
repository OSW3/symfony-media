<?php 

use OSW3\Media\Enum\Storage\Type as StorageType;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

return function (): ArrayNodeDefinition {

    $builder = new TreeBuilder('subscribers');
    $node = $builder->getRootNode();

    $node
        ->info("Specifies subscribers triggers.")
        ->useAttributeAsKey('form')
            ->arrayPrototype()
            ->useAttributeAsKey('property')
            ->scalarPrototype()->end()
        ->end();

    return $node;
};