<?php 

use OSW3\Media\Enum\File\Type;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

return function (): ArrayNodeDefinition {

    $builder = new TreeBuilder('presets');
    $node = $builder->getRootNode();

    $node
        ->info("Define some presets.")
        ->useAttributeAsKey('preset')
        ->arrayPrototype()->children()
            
            ->arrayNode('filetype')
                ->info("Specifies the type of files on which to apply the process.")
                ->scalarPrototype()
                    ->validate()->ifNotInArray(Type::toArray())
                        ->thenInvalid('Invalid file type "%s"')
                    ->end()
                ->end()
            ->end()

            ->scalarNode('processor')
                ->info("Specifies the name of the method you want to apply.")
                ->isRequired()
            ->end()

            ->arrayNode('options')
                ->info("Specifies some options to the processor.")
                ->ignoreExtraKeys(false)
                ->variablePrototype()->end()
            ->end()

            ->booleanNode('sync')
                ->info("Specifies whether the process is synchronous to the request. You can create a cron job if false")
                ->defaultTrue()
            ->end()

        ->end()->end();

    return $node;
};