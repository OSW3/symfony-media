<?php 
namespace OSW3\Media\Trait\Entity\Property;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait MediaBasenameTrait
{
    #[ORM\Column(name: 'media_basename', type: Types::STRING, length: 255, nullable: false)]
    private ?string $mediaBasename = null;

    public function getMediaBasename(): ?string
    {
        return $this->mediaBasename;
    }

    public function setMediaBasename(string $mediaBasename): static
    {
        $this->mediaBasename = $mediaBasename;

        return $this;
    }
}