<?php 
namespace OSW3\Media\Enum\Process;

use OSW3\Media\Trait\EnumTrait;

enum AudioActions: string 
{
    use EnumTrait;

    case COMPRESS  = 'compress';
    case EQUALIZE  = 'equalize';
    case FADE      = 'fade';
    case NORMALIZE = 'normalize';
    case REVERB    = 'reverb';
    case REVERSE   = 'reverse';
    case TRIM      = 'trim';
    case VOLUME    = 'volume';
}