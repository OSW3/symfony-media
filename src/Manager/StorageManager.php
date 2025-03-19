<?php 
namespace OSW3\Media\Manager;

use OSW3\CloudManager\Client;
use OSW3\Media\Enum\Storage\Type;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Filesystem\Filesystem;
use OSW3\Media\DependencyInjection\Configuration;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class StorageManager 
{
    private array $clients = [];
    private array $config;

    public function __construct(
        #[Autowire(service: 'service_container')] 
        private ContainerInterface $container,
        private Filesystem $filesystem,
        private ProviderManager $providerManager,
    ){
        $this->config = $container->getParameter(Configuration::NAME)['storages'];
    }

    public function get(string $storage): array
    {
        return $this->config[$storage];
    }



    public function copy(array &$media): static
    {
        // Retrieve storages settings from storage names
        array_walk($media['storages'], fn(&$storage) => $storage = $this->get($storage));

        foreach ($media['storages'] as $key => $storage) {
            $files = [];

            foreach ($media['aliases'] as $alias => $filename) {
                $files[$alias] = [
                    'source'      => Path::join($media['temp']->path, $filename),
                    'destination' => Path::join($storage['destination'], $filename)
                ];
            }

            // $this->storages[] = array_merge($storage, ['files' => $files]);
            $media['storages'][$key] = array_merge($storage, ['files' => $files]);
        }

        array_walk($media['storages'], function($storage) use (&$media) {
            match (Type::from($storage['type'])) {
                Type::DROPBOX => $this->copy_Dropbox($storage),
                Type::FTP     => $this->copy_FTP($storage),
                Type::LOCAL   => $this->copy_Local($storage),
                default       => null
            };

            switch ($storage['type']) {
                case Type::DROPBOX->value:
                foreach ($media['aliases'] as $alias => $filename) {
                    $media['aliases'][$alias] = $storage['files'][$alias]['location'];
                }
                break;
            }
        });
        
        return $this;
    }

    private function copy_Dropbox(array &$storage): void
    {
        $this->clients[Type::DROPBOX->value] = new Client($storage['dsn']);

        foreach ($storage['files'] as $key => $file) {
            $this->clients[Type::DROPBOX->value]->upload($file['source'], $file['destination'], true);

            $location = $this->clients[Type::DROPBOX->value]->link($file['destination']);
            $storage['files'][$key]['location'] = $location;
        }
    }

    private function copy_FTP(array $storage): void
    {
        $this->clients[Type::FTP->value] = new Client($storage['dsn']);
        $permissions = $storage['permissions'];

        array_walk($storage['files'], function($s) use ($permissions) 
        {
            $this->clients[Type::FTP->value]->uploadFile($s['source'], $s['destination']);
            if ($permissions) {
                $this->clients[Type::FTP->value]->setPermission($s['destination'], $permissions);
            }
        });
    }

    private function copy_Local(array $storage): void
    {
        array_walk($storage['files'], fn($s) => $this->filesystem->copy($s['source'], $s['destination']));
    }




    public function removeFromEntity(object $entity): static
    {
        $provider = $entity->getMediaProvider();
        $provider = $this->providerManager->get($provider);

        $storages = $provider['storages'];
        array_walk($storages, fn(&$storage) => $storage = $this->get($storage));

        $aliases = $entity->getMediaAliases();

        foreach ($storages as $key => $storage) {
            $files = [];
            foreach ($aliases as $alias => $filename) {
                array_push($files, Path::join($storage['destination'], $filename));
            }

            $storages[$key] = array_merge($storages[$key], ['files' => $files]);
        }

        array_walk($storages, function($storage) {
            match (Type::from($storage['type'])) {
                Type::DROPBOX => $this->remove_Dropbox($storage),
                Type::FTP     => $this->remove_FTP($storage),
                Type::LOCAL   => $this->remove_Local($storage),
                default       => null
            };
        });
        
        return $this;
    }

    public function remove_Dropbox(array $storage): void
    {
        // ..
    }
    public function remove_FTP(array $storage): void
    {
        // ..
    }
    public function remove_Local(array $storage): void
    {
        foreach ($storage['files'] as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
}