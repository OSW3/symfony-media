<?php 
namespace OSW3\Media\Enum\File;

use OSW3\Media\Trait\EnumTrait;

enum Type: string 
{
    use EnumTrait;

    case AUDIO           = 'audio';
    case AUDIO_MPEG      = 'audio/mpeg';
    case IMAGE           = 'image';
    case IMAGE_JPG       = 'image/jpg';
    case IMAGE_JPEG      = 'image/jpeg';
    case IMAGE_PNG       = 'image/png';
    case PDF             = 'pdf';
    case APPLICATION_PDF = 'application/pdf';
    case VIDEO           = 'video';
    case VIDEO_MP4       = 'video/mp4';
}