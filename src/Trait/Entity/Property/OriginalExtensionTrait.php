<?php 
namespace OSW3\Media\Trait\Entity\Property;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait OriginalExtensionTrait
{
    #[ORM\Column(name: 'original_extension', type: Types::STRING, length: 10, nullable: false)]
    private ?string $originalExtension = null;

    public function getOriginalExtension(): ?string
    {
        return $this->originalExtension;
    }

    public function setOriginalExtension(string $originalExtension): static
    {
        $this->originalExtension = $originalExtension;

        return $this;
    }
}