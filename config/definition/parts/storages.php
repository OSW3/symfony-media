<?php 

use OSW3\Media\Enum\Storage\Type as StorageType;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

return function (): ArrayNodeDefinition {

    $builder = new TreeBuilder('storages');
    $node = $builder->getRootNode();

    $node
        ->info("Specifies storages rules.")
        ->useAttributeAsKey('storage')
        ->arrayPrototype()->children()
        
            ->enumNode('type')
                ->info('Specifies the type of storage.')
                ->values(StorageType::toArray())
                ->defaultValue(StorageType::LOCAL->value)
            ->end()

            ->scalarNode('destination')
                ->info('Specifies the path of the upload target directory.')
                ->defaultValue('public/') // '/public/uploads/images/'
            ->end()

            ->scalarNode('public')
                ->info('Specifies the path of the upload target for the public access.')
                ->defaultValue('/') // '/uploads/images/'
            ->end()

            ->scalarNode('dsn')
                ->info('Specifies the DSN phrase of the connection service.')
                ->defaultNull()
            ->end()

            // ->scalarNode('token')
            //     ->info('Specifies the token of the connection service.')
            //     ->defaultNull()
            // ->end()

            ->scalarNode('permissions')
                ->info('Specifies the permissions applied to media files.')
                ->defaultNull()
            ->end()

            ->arrayNode('defaults')
                ->info('Specifies the defaults media.')
                ->addDefaultsIfNotSet()->children()
                    ->scalarNode('image')->defaultValue('default.jpg')->end()
                    ->scalarNode('video')->defaultValue('default.mp4')->end()
                    ->scalarNode('audio')->defaultValue('default.mp3')->end()
                    ->scalarNode('pdf')->defaultValue('default.pdf')->end()
                ->end()
            ->end()

        ->end()->end();

    return $node;
};