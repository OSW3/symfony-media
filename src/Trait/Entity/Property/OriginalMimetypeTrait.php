<?php 
namespace OSW3\Media\Trait\Entity\Property;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait OriginalMimetypeTrait
{    
    #[ORM\Column(name: 'original_mimetype', type: Types::STRING, length: 80, nullable: false)]
    private ?string $originalMimetype = null;

    public function getOriginalMimetype(): ?string
    {
        return $this->originalMimetype;
    }

    public function setOriginalMimetype(string $originalMimetype): static
    {
        $this->originalMimetype = $originalMimetype;

        return $this;
    }
}