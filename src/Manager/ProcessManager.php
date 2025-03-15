<?php 
namespace OSW3\Media\Manager;

use OSW3\Media\Enum\File\Type;
use OSW3\Media\Processor\PdfProcessor;
use Symfony\Component\Filesystem\Path;
use OSW3\Media\Processor\AudioProcessor;
use OSW3\Media\Processor\ImageProcessor;
use OSW3\Media\Processor\VideoProcessor;
use Symfony\Component\Filesystem\Filesystem;
use OSW3\Media\DependencyInjection\Configuration;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class ProcessManager 
{
    private array $processes;

    public function __construct(
        #[Autowire(service: 'service_container')] 
        private ContainerInterface $container,
        private Filesystem $filesystem,
        private AudioProcessor $audiProcessor,
        private ImageProcessor $imageProcessor,
        private PdfProcessor $pdfProcessor,
        private VideoProcessor $videoProcessor,
    ){
        $this->processes = $container->getParameter(Configuration::NAME)['presets'];
    }

    
    public function prepare(array &$media): static
    {
        // Move uploaded file to the temp dir

        $originFile = $media['temp']->pathname;
        $targetFile = Path::join($media['provider']->temp, $media['source']->filename);
        $this->filesystem->copy($originFile, $targetFile);

        if (file_exists($targetFile)) {
            $media['temp'] = (object) [
                "path"     => $media['provider']->temp,
                "pathname" => $targetFile,
                "filename" => $media['source']->filename,
                "basename" => $media['source']->basename,
            ];
        }

        return $this;
    }

    public function execute(array $presets, array $file)
    {
        // Retrieve presets settings from presets names
        array_walk($presets, fn(&$preset) => $preset = $this->processes[$preset]);

        // Sanitize presets, exclude presets if filetype not match
        $presets = array_filter($presets, fn($preset) => !!array_intersect([
            $file['source']->type, 
            $file['source']->mimetype
        ], $preset['filetype']));

        array_walk($presets, function($preset) use($file) {
            
            $processor = $preset['processor'];
            $options   = $preset['options'];
            $sync      = $preset['sync'];

            if ($this->container->has($processor)) {
                $instance = $this->container->get($processor);
                $instance->processing($file, $options);
            }


            // dump($this->container->get($processor));

            // dump(class_exists($processor));
            // dump($this->container);
            // dump($processor);
            // dump($options);
            // dump($sync);
            dump('---');
        });

        // dump($presets);
        // dd($file);
    }







    // public function getAll(): array
    // {
    //     return $this->processes;
    // }

    // public function get(string $storage): array
    // {
    //     return $this->processes[$storage];
    // }





    // public function aliases(array $processes, string $filetype, array $options): array
    // {
    //     return array_map(fn($process) => match (Type::from($filetype)) {
    //         Type::AUDIO => $this->audiProcessor->getAlias($process, $options),
    //         Type::IMAGE => $this->imageProcessor->getAlias($process, $options),
    //         Type::PDF   => $this->pdfProcessor->getAlias($process, $options),
    //         Type::VIDEO => $this->videoProcessor->getAlias($process, $options),
    //         default     => null
    //     }, $processes);
    // }

    // public function execute(array $processes, string $filetype) 
    // {
    //     array_walk($processes, fn($process) => match (Type::from($filetype)) {
    //         Type::AUDIO => $this->audiProcessor->execute($process),
    //         Type::IMAGE => $this->imageProcessor->execute($process),
    //         Type::PDF   => $this->pdfProcessor->execute($process),
    //         Type::VIDEO => $this->videoProcessor->execute($process),
    //         default     => null
    //     });
    // }
}