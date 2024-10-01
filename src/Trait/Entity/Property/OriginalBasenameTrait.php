<?php 
namespace OSW3\Media\Trait\Entity\Property;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait OriginalBasenameTrait
{    
    #[ORM\Column(name: 'original_basename', type: Types::STRING, length: 255, nullable: false)]
    private ?string $originalBasename = null;

    public function getOriginalBasename(): ?string
    {
        return $this->originalBasename;
    }

    public function setOriginalBasename(string $originalBasename): static
    {
        $this->originalBasename = $originalBasename;

        return $this;
    }
}