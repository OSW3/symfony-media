<?php 
namespace OSW3\Media\Processor;

use Symfony\Component\HttpFoundation\File\UploadedFile;

abstract class AbstractProcessor
{
    public function execute(array $process) {

        $action = $process['action'];
        $input = $process['input'];
        $output = $process['output'];
        $options = $process['options'];

        // dump($action);
        // dump($options);
        // dump($source);
        // dump("---");

        if (method_exists($this, $action)) {
            return $this->$action($input, $output, $options);
        }
    
        // throw new \Exception("Action '{$action}' does not exist.");
    }
    public function prepare(array $process, array $media): array 
    {

        $action = $process['action'];
        $options = $process['options'];

        dump('PREPARE');
        // dump($action);
        // dump($options);
        // dump($source);
        // dump("---");

        // if (method_exists($this, $action)) {
        //     return $this->$action($source, $options);
        // }
    
        // throw new \Exception("Action '{$action}' does not exist.");

        return [];
    }

    public function getAlias(array $process, array $media): ?array {
        return null;
    }


    protected function generateOutputFilename(string $name, string $extension, array $process): string
    {
        $filename = $name;

        if (isset($process['options']['alias'])) {
            $filename .= "-{$process['options']['alias']}";
        }

        return "{$filename}.{$extension}";
    }


}