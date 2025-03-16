<?php 
namespace OSW3\Media\Interfaces;

interface ProcessorInterface 
{
    public function processing(array &$source, array $options): void;
    public function getAliases(): array;
}