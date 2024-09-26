<?php

use OSW3\Media\Enum\File\NameStrategy;
use OSW3\Media\Enum\Storage\Type as StorageType;

return static function($definition)
{
    $definition->rootNode()->children()

        ->arrayNode('storages')
            ->info("Specifies storages rules")
            ->useAttributeAsKey('storage')  // Storage name
            ->arrayPrototype()
                ->children()

                    ->enumNode('type')
                        ->info('Specifies the type of storage.')
                        ->values(StorageType::toArray())
                        ->defaultValue(StorageType::LOCAL->value)
                    ->end()

                    ->scalarNode('targetPath')
                        ->info('Specifies the path of the upload target directory.')
                        ->defaultValue('public/') // '/public/uploads/images/'
                    ->end()

                    ->scalarNode('publicPath')
                        ->info('Specifies the path of the upload target for the public access.')
                        ->defaultValue('/') // '/uploads/images/'
                    ->end()

                    ->scalarNode('dsn')
                        ->info('Specifies the DSN phrase of the connection service.')
                        ->defaultNull()
                    ->end()

                    ->scalarNode('token')
                        ->info('Specifies the token of the connection service.')
                        ->defaultNull()
                    ->end()

                ->end()
            ->end() // end storage name
        ->end() // end: storages


        ->arrayNode('providers')
            ->info("Specifies medias providers")
            ->useAttributeAsKey('provider') // provider name
            ->arrayPrototype() 
                ->children()

                    ->enumNode('nameStrategy')
                        ->info('Specifies the naming strategy for the target files.')
                        ->values(NameStrategy::toArray())
                        ->defaultValue(NameStrategy::ORIGINAL->value)
                    ->end()

                    ->arrayNode('storages')
                        ->info('Specifies the name of the storage definition.')
                        ->isRequired()
                        ->cannotBeEmpty()
                        ->scalarPrototype()->end()
                    ->end()
                
                ->end()
            ->end() // end provider name
        ->end() // end: providers

    ->end();

    
    $definition->rootNode()->validate()->always(function ($config) {

        // Storage Exception
        array_walk($config['storages'], function($storage, $name) {
            switch ($storage['type']) {
                case StorageType::DROPBOX->value: 
                    if ($storage['dsn'] === null && $storage['token'] === null) {
                        throw new \Exception(sprintf("The DSN property or Token property must be defined for a storage definition \"%s\" with type \"%s\"", $name, $storage['type']));
                    }
                break;

                case StorageType::FTP->value: 
                    if ($storage['dsn'] === null) {
                        throw new \Exception(sprintf("The DSN property is required for a storage definition \"%s\" with type \"%s\"", $name, $storage['type']));
                    }
                break;
            
                case StorageType::LOCAL->value: 
                break;
            }
        });
        
        return $config;

    })->end();
};