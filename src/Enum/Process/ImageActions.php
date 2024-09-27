<?php 
namespace OSW3\Media\Enum\Process;

use OSW3\Media\Trait\EnumTrait;

enum ImageActions: string 
{
    use EnumTrait;

    case BLUR       = 'blur';
    case BRIGHTNESS = 'brightness';
    case CONTRAST   = 'contrast';
    case CONVERT    = 'convert';
    case CROP       = 'crop';
    case FLIP       = 'flip';
    case GRAYSCALE  = 'grayscale';
    case RESIZE     = 'resize';
    case ROTATE     = 'rotate';
    case WATERMARK  = 'watermark';
}