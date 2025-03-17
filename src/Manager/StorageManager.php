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
    private array $storages = [];

    public function __construct(
        #[Autowire(service: 'service_container')] 
        private ContainerInterface $container,
        private Filesystem $filesystem,
    ){
        $this->config = $container->getParameter(Configuration::NAME)['storages'];
    }

    public function get(string $storage): array
    {
        return $this->config[$storage];
    }

    public function prepare(array $media): static
    {
        // Retrieve storages settings from storage names
        array_walk($media['storages'], fn(&$storage) => $storage = $this->get($storage));

        foreach ($media['storages'] as $storage) {
            $files = [];

            foreach ($media['aliases'] as $alias => $filename) {
                $files[$alias] = [
                    'source'      => Path::join($media['temp']->path, $filename),
                    'destination' => Path::join($storage['destination'], $filename)
                ];
            }

            $this->storages[] = array_merge($storage, ['files' => $files]);
        }
        
        return $this;
    }

    public function execute()
    {
        array_walk($this->storages, function($storage) {
            match (Type::from($storage['type'])) {
                Type::DROPBOX => $this->storageClient_Dropbox($storage),
                Type::FTP     => $this->storageClient_FTP($storage),
                Type::LOCAL   => $this->storageClient_Local($storage),
                default       => null
            };
        });
    }

    private function storageClient_Dropbox(array $storage)
    {
        $this->clients[Type::DROPBOX->value] = new Client($storage['dsn']);
        array_walk($storage['files'], fn($s) => $this->clients[Type::DROPBOX->value]->uploadFile($s['source'], $s['target']));
    }

    private function storageClient_FTP(array $storage)
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

    private function storageClient_Local(array $storage)
    {
        array_walk($storage['files'], fn($s) => $this->filesystem->copy($s['source'], $s['destination']));
    }
}