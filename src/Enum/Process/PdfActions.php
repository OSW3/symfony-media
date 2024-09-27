<?php 
namespace OSW3\Media\Enum\Process;

use OSW3\Media\Trait\EnumTrait;

enum PdfActions: string 
{
    use EnumTrait;

    case APPEND    = 'append';
    case COMPRESS  = 'compress';
    case EXTRACT   = 'extract';
    case MERGE     = 'merge';
    case PASSWORD  = 'password';
    case PREPEND   = 'prepend';
    case ROTATE    = 'rotate';
    case SPLIT     = 'split';
    case WATERMARK = 'watermark';
}