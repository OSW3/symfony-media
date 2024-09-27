<?php 
namespace OSW3\Media\Processor;

use Symfony\Component\HttpFoundation\File\UploadedFile;

abstract class AbstractProcessor
{
    public function execute(array $process, UploadedFile $source) {

        $action = $process['action'];
        $options = $process['options'];

        dump($action);
        dump($options);
        dump($source);
        dump("---");

        // if (method_exists($this, $action)) {
        //     return $this->$action($process['options']);
        // }
    
        // throw new \Exception("Action '{$action}' does not exist.");
    }
}