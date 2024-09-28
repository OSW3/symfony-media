<?php 
namespace OSW3\Media\Processor;

use claviska\SimpleImage;
use Symfony\Component\Filesystem\Path;
use OSW3\Media\Processor\AbstractProcessor;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ImageProcessor extends AbstractProcessor
{
    private SimpleImage $processor;

    public function __construct(
        private Filesystem $filesystem,
    )
    {
        $this->processor = new SimpleImage;
    }

    public function prepare(array $process, array $media): array 
    {
        $action  = $process['action'];
        $options = $process['options'];

        $output  = Path::join($media['tempPath'], $this->generateOutputFilename(
            name     : $media['media']['basename'],
            extension: $media['media']['extension'],
            process  : $process,
        ));

        return [
            'input'   => $media['file']['pathname'],
            'output'  => $output,
            'action'  => $action,
            'options' => $options,
        ];
    }


    public function getAlias(array $process, array $media): ?array {

        $action  = $process['action'];

        if (!in_array($action, ['resize'])) {
            return null;
        }

        $width    = $process['options']['width'] ?? null;
        $height   = $process['options']['height'] ?? null;
        $name   = $process['options']['alias'] ?? null;

        $filename = $this->generateOutputFilename(
            name     : $media['media']['basename'],
            extension: $media['media']['extension'],
            process  : $process,
        );

        return [
            'name' => $name,
            'filename' => $filename,
            'height'   => $height,
            'width'    => $width,
        ];
    }


    // public function fromFile($file) {
    //     $this->processor->fromFile($file);
    // }

    // public function execute(string $action, array $options=[]) 
    // {
    //     if (method_exists($this, $action)) {
    //         return $this->$action($options);
    //     }
    
    //     throw new \Exception("Action '{$action}' does not exist.");
    // }

    public function watermark(string $input, string $output, array $options=[])
    {
        // dump('WATERMARK');
        // dump($options);
    }

    public function resize(string $input, string $output, array $options=[])
    {
        $width  = $options['width'] ?? null;
        $height = $options['height'] ?? null;

        $this->filesystem->mkdir(pathinfo($output, PATHINFO_DIRNAME));

        $this->processor
            ->fromFile( $input )
            ->bestFit( $height, $width )
            ->toFile( $output )
        ;
    }
}