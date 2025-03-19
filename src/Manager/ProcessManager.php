<?php 
namespace OSW3\Media\Manager;

use Symfony\Component\Filesystem\Path;
use Symfony\Component\Filesystem\Filesystem;
use OSW3\Media\DependencyInjection\Configuration;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class ProcessManager 
{
    private array $processes;
    private array $aliases = [];
    private array $presets = [];
    private array $media = [];

    public function __construct(
        #[Autowire(service: 'service_container')] 
        private ContainerInterface $container,
        private Filesystem $filesystem
    ){
        $this->processes = $container->getParameter(Configuration::NAME)['presets'];
    }
    
    public function prepare(array &$media): static
    {
        // Move uploaded file to the temp dir
        // --

        $path       = $media['provider']->temp;
        $extension  = $media['source']->extension;
        $basename   = $media['basename'];
        $filename   = "{$basename}.{$extension}";
        $pathname   = Path::join($media['provider']->temp, $filename);

        $this->filesystem->copy($media['temp']->pathname, $pathname);

        if (file_exists($pathname)) {
            $media['temp'] = (object) [
                "path"     => $path,
                "pathname" => $pathname,
                "filename" => $filename,
                "basename" => $basename,
            ];
        }


        // Retrieve presets settings from preset names
        // --

        array_walk($media['presets'], fn(&$preset) => $preset = $this->processes[$preset]);

        $this->media = $media;
        $this->presets = $media['presets'];

        return $this;
    }

    public function execute()
    {
        // Retrieve presets settings from preset names
        $presets = $this->presets;
        $media = $this->media;

        // Sanitize presets, exclude presets if filetype not match
        $presets = array_filter($presets, fn($preset) => !!array_intersect([
            $media['source']->type, 
            $media['source']->mimetype
        ], $preset['filetype']));

        array_walk($presets, function($preset) use($media) {
            
            $processor = $preset['processor'];
            $options   = $preset['options'];
            $delay     = $preset['delay'];

            if (!$this->container->has($processor)) {
                throw new \Exception(sprintf("The processor %s is not found", $processor));
                // return;
            }

            if ($delay <= 0) {
                $instance = $this->container->get($processor);
                $instance->processing($media, $options);
    
                $this->aliases = array_merge($this->aliases, $instance->getAliases());
            }
            else {
                // dump("{$processor} delay {$delay}");
                // dump($media);
            }
        });
    }

    public function getAliases(): array
    {
        return $this->aliases;
    }
}