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
    private array $storages;

    public function __construct(
        #[Autowire(service: 'service_container')] private ContainerInterface $container,
        private Filesystem $filesystem,
    ){
        $config = $container->getParameter(Configuration::NAME);
        $this->storages = $config['storages'];
    }

    public function getAll(): array
    {
        return $this->storages;
    }

    public function get(string $storage): array
    {
        return $this->storages[$storage];
    }

    public function prepare(array $storages, array $media): array
    {
        return array_map(function($storage) use ($media) {

            if ($storage['type'] === Type::LOCAL->value) {
                $targetPath = Path::join($this->container->get('kernel')->getProjectDir(), $storage['targetPath']);
                $storage['targetPath'] = $targetPath;
            }

            $storage['files'] = [];

            $storage['files']['original'] = [
                'source' => $media['file']['pathname'],
                'target' => Path::join($storage['targetPath'], $media['media']['filename'])
            ];

            foreach ($media['aliases'] as $alias) {
                $storage['files'][$alias['name']] = [
                    'source' => Path::join($media['tempPath'], $alias['filename']),
                    'target' => Path::join($storage['targetPath'], $alias['filename'])
                ];
            }

            return $storage;

        }, $storages);
    }

    public function execute(array $storages)
    {
        array_walk($storages, function($storage) {
            match (Type::from($storage['type'])) {
                Type::DROPBOX => $this->storageClient_Dropbox($storage),
                Type::FTP     => $this->storageClient_FTP($storage),
                Type::LOCAL   => $this->storageClient_Local($storage),
                default       => null
            };
        });
    }

    private function storageClient_Connection(Type $type, $dsn) 
    {
        if (!isset($this->clients[$type->value])) {
            $this->clients[$type->value] = new Client($dsn);
        }
    }

    private function storageClient_Dropbox($storage) 
    {
        $dsn   = "dropbox:token://{$storage['token']}";
        $files = $storage['files'];

        $this->storageClient_Connection(Type::DROPBOX, $dsn);

        array_walk($files, fn($entry) => $this->clients[Type::DROPBOX->value]->uploadFile($entry['source'], $entry['target']));
    }

    private function storageClient_FTP($storage)
    {
        $dsn         = "ftp://{$storage['dsn']}";
        $files       = $storage['files'];
        $permissions = $storage['permissions'];

        $this->storageClient_Connection(Type::FTP, $dsn);

        array_walk($files, function($entry) use ($permissions) {
            $this->clients[Type::FTP->value]->uploadFile($entry['source'], $entry['target']);
            if ($permissions) {
                $this->clients[Type::FTP->value]->setPermission($entry['target'], $permissions);
            }
        });
    }

    private function storageClient_Local($storage)
    {
        $files = $storage['files'];
        array_walk($files, fn($entry) => $this->filesystem->copy($entry['source'], $entry['target']));
    }
}