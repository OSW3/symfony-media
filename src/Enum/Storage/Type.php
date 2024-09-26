<?php 
namespace OSW3\Media\Enum\Storage;

use OSW3\Media\Trait\EnumTrait;

enum Type: string 
{
    use EnumTrait;

    case DROPBOX = 'dropbox';
    case FTP     = 'ftp';
    case LOCAL   = 'local';
}