<?php 
namespace OSW3\Media\Enum\File;

use OSW3\Media\Trait\EnumTrait;

enum NameStrategy: string 
{
    use EnumTrait;

    case ORIGINAL = 'original';
    case MD5      = 'md5';
    case UNIQUE   = 'unique';
    case DATETIME = 'datetime';
    case RANDOM   = 'random';
}