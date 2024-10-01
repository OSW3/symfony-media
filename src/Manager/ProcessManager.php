<?php 
namespace OSW3\Media\Manager;

use OSW3\Media\Enum\File\Type;
use OSW3\Media\Processor\PdfProcessor;
use OSW3\Media\Processor\AudioProcessor;
use OSW3\Media\Processor\ImageProcessor;
use OSW3\Media\Processor\VideoProcessor;
use OSW3\Media\DependencyInjection\Configuration;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class ProcessManager 
{
    private array $processes;

    public function __construct(
        #[Autowire(service: 'service_container')] private ContainerInterface $container,
        private AudioProcessor $audiProcessor,
        private ImageProcessor $imageProcessor,
        private PdfProcessor $pdfProcessor,
        private VideoProcessor $videoProcessor,
    ){
        $config = $container->getParameter(Configuration::NAME);
        $this->processes = $config['processing'];
    }

    public function getAll(): array
    {
        return $this->processes;
    }

    public function get(string $storage): array
    {
        return $this->processes[$storage];
    }

    public function prepare(array $processes, string $filetype, array $options): array
    { 
        return array_map(fn($process) => match (Type::from($filetype)) {
            Type::AUDIO => $this->audiProcessor->prepare($process, $options),
            Type::IMAGE => $this->imageProcessor->prepare($process, $options),
            Type::PDF   => $this->pdfProcessor->prepare($process, $options),
            Type::VIDEO => $this->videoProcessor->prepare($process, $options),
            default     => null
        }, $processes);
    }

    public function aliases(array $processes, string $filetype, array $options): array
    {
        return array_map(fn($process) => match (Type::from($filetype)) {
            Type::AUDIO => $this->audiProcessor->getAlias($process, $options),
            Type::IMAGE => $this->imageProcessor->getAlias($process, $options),
            Type::PDF   => $this->pdfProcessor->getAlias($process, $options),
            Type::VIDEO => $this->videoProcessor->getAlias($process, $options),
            default     => null
        }, $processes);
    }

    public function execute(array $processes, string $filetype) 
    {
        array_walk($processes, fn($process) => match (Type::from($filetype)) {
            Type::AUDIO => $this->audiProcessor->execute($process),
            Type::IMAGE => $this->imageProcessor->execute($process),
            Type::PDF   => $this->pdfProcessor->execute($process),
            Type::VIDEO => $this->videoProcessor->execute($process),
            default     => null
        });
    }
}