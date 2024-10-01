<?php 
namespace OSW3\Media\Trait\Entity\Property;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait OriginalFilenameTrait
{    
    #[ORM\Column(name: 'original_filename', type: Types::STRING, length: 255, nullable: false)]
    private ?string $originalFilename = null;

    public function getOriginalFilename(): ?string
    {
        return $this->originalFilename;
    }

    public function setOriginalFilename(string $originalFilename): static
    {
        $this->originalFilename = $originalFilename;

        return $this;
    }
}