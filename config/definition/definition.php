<?php

use OSW3\Media\Enum\File\NameStrategy;
use OSW3\Media\Enum\File\Type;
use OSW3\Media\Enum\Process\AudioActions;
use OSW3\Media\Enum\Process\ImageActions;
use OSW3\Media\Enum\Process\PdfActions;
use OSW3\Media\Enum\Process\VideoActions;
use OSW3\Media\Enum\Storage\Type as StorageType;
use Symfony\Component\Filesystem\Path;

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


        ->arrayNode('processing')
            ->info("Specifies processing rules")
            ->useAttributeAsKey('process')  // Process name
            ->arrayPrototype()
                ->arrayPrototype()
                    ->children()

                        ->scalarNode('action')
                            ->isRequired()
                        ->end()

                        ->enumNode('mode')
                            ->info('Specifies when the process will be executed.')
                            ->values(['sync','delayed'])
                            ->defaultValue('sync')
                        ->end()

                        ->arrayNode('options')
                            ->useAttributeAsKey('option')
                            ->scalarPrototype()->end()
                        ->end()

                        ->arrayNode('filetype')
                            ->scalarPrototype()
                            
                                ->validate()
                                ->ifNotInArray(Type::toArray())
                                    ->thenInvalid('Invalid file type "%s"')
                                ->end()

                            ->end()
                        ->end()

                    ->end()
                ->end()
            ->end() // end process name
        ->end() // end: processing


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

                    // ->scalarNode('tempPath')
                    //     ->info('Specifies the path of temporary file storage.')
                    //     ->cannotBeEmpty()
                    //     ->defaultValue(Path::join(__DIR__, '../../', 'temp'))
                    // ->end()

                    ->scalarNode('datetimeFormat')
                        ->info('Specifies the format of the datetime of the nameStrategy with value datetime.')
                        ->cannotBeEmpty()
                        ->defaultValue('YmzHis')
                    ->end()

                    ->arrayNode('storages')
                        ->info('Specifies the name of the storage definition.')
                        ->isRequired()
                        ->cannotBeEmpty()
                        ->scalarPrototype()->end()
                    ->end()

                    ->arrayNode('processes')
                        ->info('Specifies the name of the process definition.')
                        // ->isRequired()
                        ->cannotBeEmpty()
                        ->defaultValue([])
                        ->scalarPrototype()->end()
                    ->end()
                
                ->end()
            ->end() // end provider name
        ->end() // end: providers

    ->end();

    
    $definition->rootNode()->validate()->always(function ($config) {

        // Storages Exceptions
        // --
        array_walk($config['storages'], function($options, $name) {
            switch ($options['type']) {
                case StorageType::DROPBOX->value: 
                    if ($options['dsn'] === null && $options['token'] === null) {
                        throw new \Exception(sprintf("The DSN property or Token property must be defined for a storage definition \"%s\" with type \"%s\"", $name, $options['type']));
                    }
                break;

                case StorageType::FTP->value: 
                    if ($options['dsn'] === null) {
                        throw new \Exception(sprintf("The DSN property is required for a storage definition \"%s\" with type \"%s\"", $name, $options['type']));
                    }
                break;
            
                case StorageType::LOCAL->value: 
                break;
            }
        });

        // Processing Exceptions
        // --
        array_walk($config['processing'], function($processes, $name) {
            foreach ($processes as $process) {
                foreach ($process['filetype'] as $filetype) switch (Type::from($filetype)) {

                    case Type::AUDIO:
                    case Type::AUDIO_MPEG:

                        // Todo: Dependency Audio 

                        // Filetype Actions Exception
                        if (!in_array($process['action'], AudioActions::toArray())) {
                            throw new \Exception(sprintf("The action \"%s\" is not valid for the \"%s\" filetype in the process \"%s\"", $process['action'], $filetype, $name));
                        }

                        // 
                        switch (AudioActions::from($process['action'])) {
                            case AudioActions::COMPRESS:
                                $validOptions   = ['bitrate'];
                                $invalidOptions = array_diff(array_keys($process['options']), $validOptions);
                            break;
                            case AudioActions::EQUALIZE:
                                $validOptions   = ['frequency', 'gain'];
                                $invalidOptions = array_diff(array_keys($process['options']), $validOptions);
                            break;
                            case AudioActions::FADE:
                                $validOptions   = ['type','duration'];
                                $invalidOptions = array_diff(array_keys($process['options']), $validOptions);
                            break;
                            case AudioActions::REVERB:
                                $validOptions   = ['level'];
                                $invalidOptions = array_diff(array_keys($process['options']), $validOptions);
                            break;
                            case AudioActions::TRIM:
                                $validOptions   = ['start','duration'];
                                $invalidOptions = array_diff(array_keys($process['options']), $validOptions);
                            break;
                            case AudioActions::VOLUME:
                                $validOptions   = ['level'];
                                $invalidOptions = array_diff(array_keys($process['options']), $validOptions);
                            break;
                        }
                        if (!empty($invalidOptions)) {
                            throw new \Exception(sprintf("Options \"%s\" is/are not valid for \"%s\" action (filetype: %s) in the processing \"%s\"", implode(', ', $invalidOptions), $process['action'], $filetype, $name));
                        }

                    break;

                    case Type::IMAGE:
                    case Type::IMAGE_JPEG:
                    case Type::IMAGE_JPG:
                    case Type::IMAGE_PNG:

                        if (!class_exists("\claviska\SimpleImage")) {
                            throw new \Exception(sprintf("claviska/simpleimage is required to execute image process.\n\ncomposer require claviska/simpleimage\n\n"));
                        }

                        // Filetype Actions Exception
                        if (!in_array($process['action'], ImageActions::toArray())) {
                            throw new \Exception(sprintf("The action \"%s\" is not valid for the \"%s\" filetype in the process \"%s\"", $process['action'], $filetype, $name));
                        }

                        // 
                        switch (ImageActions::from($process['action'])) {
                            case ImageActions::BLUR:
                                $validOptions   = ['level'];
                                $invalidOptions = array_diff(array_keys($process['options']), $validOptions);
                            break;
                            case ImageActions::BRIGHTNESS:
                                $validOptions   = ['level'];
                                $invalidOptions = array_diff(array_keys($process['options']), $validOptions);
                            break;
                            case ImageActions::CONTRAST:
                                $validOptions   = ['level'];
                                $invalidOptions = array_diff(array_keys($process['options']), $validOptions);
                            break;
                            case ImageActions::CONVERT:
                                $validOptions   = ['format'];
                                $invalidOptions = array_diff(array_keys($process['options']), $validOptions);
                            break;
                            case ImageActions::CROP:
                                $validOptions   = ['width', 'height', 'x', 'y'];
                                $invalidOptions = array_diff(array_keys($process['options']), $validOptions);
                            break;
                            case ImageActions::FLIP:
                                $validOptions   = ['direction'];
                                $invalidOptions = array_diff(array_keys($process['options']), $validOptions);
                            break;
                            case ImageActions::RESIZE:
                                $validOptions   = ['alias','height','width'];
                                $invalidOptions = array_diff(array_keys($process['options']), $validOptions);
                            break;
                            case ImageActions::ROTATE:
                                $validOptions   = ['angle'];
                                $invalidOptions = array_diff(array_keys($process['options']), $validOptions);
                            break;
                            case ImageActions::WATERMARK:
                                $validOptions   = ['text', 'position'];
                                $invalidOptions = array_diff(array_keys($process['options']), $validOptions);
                            break;
                        }
                        if (!empty($invalidOptions)) {
                            throw new \Exception(sprintf("Options \"%s\" is/are not valid for \"%s\" action (filetype: %s) in the processing \"%s\"", implode(', ', $invalidOptions), $process['action'], $filetype, $name));
                        }

                    break;

                    case Type::PDF:
                    case Type::APPLICATION_PDF:

                        // Todo: Dependency PDF 

                        // Filetype Actions Exception
                        if (!in_array($process['action'], PdfActions::toArray())) {
                            throw new \Exception(sprintf("The action \"%s\" is not valid for the \"%s\" filetype in the process \"%s\"", $process['action'], $filetype, $name));
                        }

                        // 
                        switch (PdfActions::from($process['action'])) {
                            case PdfActions::APPEND:
                                $validOptions   = ['file'];
                                $invalidOptions = array_diff(array_keys($process['options']), $validOptions);
                            break;
                            case PdfActions::COMPRESS:
                                $validOptions   = ['level'];
                                $invalidOptions = array_diff(array_keys($process['options']), $validOptions);
                            break;
                            case PdfActions::EXTRACT:
                                $validOptions   = ['pages'];
                                $invalidOptions = array_diff(array_keys($process['options']), $validOptions);
                            break;
                            case PdfActions::MERGE:
                                $validOptions   = ['files'];
                                $invalidOptions = array_diff(array_keys($process['options']), $validOptions);
                            break;
                            case PdfActions::PASSWORD:
                                $validOptions   = ['password'];
                                $invalidOptions = array_diff(array_keys($process['options']), $validOptions);
                            break;
                            case PdfActions::PREPEND:
                                $validOptions   = ['file'];
                                $invalidOptions = array_diff(array_keys($process['options']), $validOptions);
                            break;
                            case PdfActions::ROTATE:
                                $validOptions   = ['pages','angle'];
                                $invalidOptions = array_diff(array_keys($process['options']), $validOptions);
                            break;
                            case PdfActions::SPLIT:
                                $validOptions   = ['pages']; // [1-3, 5]
                                $invalidOptions = array_diff(array_keys($process['options']), $validOptions);
                            break;
                            case PdfActions::WATERMARK:
                                $validOptions   = ['text','position'];
                                $invalidOptions = array_diff(array_keys($process['options']), $validOptions);
                            break;
                        }
                        if (!empty($invalidOptions)) {
                            throw new \Exception(sprintf("Options \"%s\" is/are not valid for \"%s\" action (filetype: %s) in the processing \"%s\"", implode(', ', $invalidOptions), $process['action'], $filetype, $name));
                        }

                    break;

                    case Type::VIDEO:
                    case Type::VIDEO_MP4:

                        // Todo: Dependency Video 

                        // Filetype Actions Exception
                        if (!in_array($process['action'], VideoActions::toArray())) {
                            throw new \Exception(sprintf("The action \"%s\" is not valid for the \"%s\" filetype in the process \"%s\"", $process['action'], $filetype, $name));
                        }

                        // 
                        switch (VideoActions::from($process['action'])) {
                            case VideoActions::COMPRESS:
                                $validOptions   = ['bitrate'];
                                $invalidOptions = array_diff(array_keys($process['options']), $validOptions);
                            break;
                            case VideoActions::CONVERT:
                                $validOptions   = ['format'];
                                $invalidOptions = array_diff(array_keys($process['options']), $validOptions);
                            break;
                            case VideoActions::FADE:
                                $validOptions   = ['type','duration'];
                                $invalidOptions = array_diff(array_keys($process['options']), $validOptions);
                            break;
                            case VideoActions::OVERLAY:
                                $validOptions   = ['file','position'];
                                $invalidOptions = array_diff(array_keys($process['options']), $validOptions);
                            break;
                            case VideoActions::SPEED:
                                $validOptions   = ['factor'];
                                $invalidOptions = array_diff(array_keys($process['options']), $validOptions);
                            break;
                            case VideoActions::THUMBNAIL:
                                $validOptions   = ['time'];
                                $invalidOptions = array_diff(array_keys($process['options']), $validOptions);
                            break;
                            case VideoActions::TRIM:
                                $validOptions   = ['start','duration'];
                                $invalidOptions = array_diff(array_keys($process['options']), $validOptions);
                            break;
                            case VideoActions::VOLUME:
                                $validOptions   = ['level'];
                                $invalidOptions = array_diff(array_keys($process['options']), $validOptions);
                            break;
                            case VideoActions::WATERMARK:
                                $validOptions   = ['text','position'];
                                $invalidOptions = array_diff(array_keys($process['options']), $validOptions);
                            break;
                        }
                        if (!empty($invalidOptions)) {
                            throw new \Exception(sprintf("Options \"%s\" is/are not valid for \"%s\" action (filetype: %s) in the processing \"%s\"", implode(', ', $invalidOptions), $process['action'], $filetype, $name));
                        }

                    break;
                }
            }
        });

        // Providers Exceptions
        // --
        array_walk($config['providers'], function($options, $name) use ($config) {
            foreach ($options['storages'] as $storage) {
                if (!isset($config['storages'][$storage])) {
                    throw new \Exception(sprintf("The storage \"%s\" used by the provider \"%s\" is not defined in the storages definition", $storage, $name));
                }
            }
            foreach ($options['processes'] as $process) {
                if (!isset($config['processing'][$process])) {
                    throw new \Exception(sprintf("The process \"%s\" used by the provider \"%s\" is not defined in the processing definition", $process, $name));
                }
            }
        });

        return $config;

    })->end();
};