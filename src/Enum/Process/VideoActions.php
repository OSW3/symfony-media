<?php 
namespace OSW3\Media\Enum\Process;

use OSW3\Media\Trait\EnumTrait;

enum VideoActions: string 
{
    use EnumTrait;

    case COMPRESS  = 'compress';
    case CONVERT   = 'convert';
    case FADE      = 'fade';
    case MUTE      = 'mute';
    case OVERLAY   = 'overlay';
    case SPEED     = 'speed';
    case THUMBNAIL = 'thumbnail';
    case TRIM      = 'trim';
    case VOLUME    = 'volume';
    case WATERMARK = 'watermark';
}