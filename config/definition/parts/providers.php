<?php 

use OSW3\Media\Enum\File\NameStrategy;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

return function (): ArrayNodeDefinition {

    $builder = new TreeBuilder('providers');
    $node = $builder->getRootNode();

    $node
        ->info("Specifies medias providers.")
        ->useAttributeAsKey('provider')
        ->arrayPrototype()->children()
        
            ->scalarNode('entity')
                ->info('Specifies the entity that stores media data.')
                ->isRequired()
            ->end()

            ->booleanNode('unique')
                ->info('Specifies whether the media must be unique.')
                ->defaultTrue()
            ->end()

            ->booleanNode('allow_delete')
                ->info('Specifies whether the media can be deleted.')
                ->defaultTrue()
            ->end()

            ->booleanNode('allow_update')
                ->info('Specifies whether the media can be update.')
                ->defaultTrue()
            ->end()

            ->arrayNode('filename')
            ->info('Specifies filename rules.')
            ->addDefaultsIfNotSet()->children()

                ->enumNode('strategy')
                    ->info('Specifies the naming strategy for the target files.')
                    ->values(NameStrategy::toArray())
                    ->defaultValue(NameStrategy::ORIGINAL->value)
                ->end()

                ->scalarNode('prefix')
                    ->info('Specifies the prefix of the file name.')
                    ->defaultNull()
                ->end()

                ->scalarNode('suffix')
                    ->info('Specifies the suffix of the file name.')
                    ->defaultNull()
                ->end()

                ->scalarNode('datetimeFormat')
                    ->info('Specifies the format of the datetime of the datetime strategy.')
                    ->defaultValue('YmzHis')
                ->end()

                ->integerNode('length')
                    ->info('Specifies the length of the random strategy.')
                    ->defaultValue(10)
                ->end()

            ->end()->end()

            ->scalarNode('temp')
                ->info('Specifies the path of temporary file storage.')
                ->cannotBeEmpty()
                ->defaultValue('%kernel.project_dir%/var/temp')
            ->end()

            ->arrayNode('storages')
                ->info('Specifies names of the storages.')
                ->isRequired()
                ->cannotBeEmpty()
                ->scalarPrototype()->end()
            ->end()

            ->arrayNode('presets')
                ->info('Specifies the names of the presets that will be executed.')
                ->cannotBeEmpty()
                ->defaultValue([])
                ->scalarPrototype()->end()
            ->end()

        ->end()->end();

    return $node;
};