<?php 
namespace OSW3\Media\Processor;

use claviska\SimpleImage;
use OSW3\Media\Processor\AbstractProcessor;

final class ImageProcessor extends AbstractProcessor
{
    private SimpleImage $processor;

    public function __construct()
    {
        $processor = new SimpleImage;
    }


    public function fromFile($file) {
        $this->processor->fromFile($file);
    }

    // public function execute(string $action, array $options=[]) 
    // {
    //     if (method_exists($this, $action)) {
    //         return $this->$action($options);
    //     }
    
    //     throw new \Exception("Action '{$action}' does not exist.");
    // }

    public function watermark(array $options=[])
    {
        dump($options);
    }

    public function resize(array $options=[])
    {
        // list($height, $width) = $options;

        // $this->processor->resize($height, $width);
        // $this->processor->bestFit($height, $width);

        dump($options);
    }
}