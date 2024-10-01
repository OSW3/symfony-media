<?php 
namespace OSW3\Media\Trait\Entity\Property;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait MediaFilenameTrait
{
    #[ORM\Column(name: 'media_filename', type: Types::STRING, length: 255, nullable: false)]
    private ?string $mediaFilename = null;

    public function getMediaFilename(): ?string
    {
        return $this->mediaFilename;
    }

    public function setMediaFilename(string $mediaFilename): static
    {
        $this->mediaFilename = $mediaFilename;

        return $this;
    }
}