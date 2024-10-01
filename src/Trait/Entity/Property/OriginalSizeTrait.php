<?php 
namespace OSW3\Media\Trait\Entity\Property;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait OriginalSizeTrait
{    
    #[ORM\Column(name: 'original_size', type: Types::INTEGER, nullable: false)]
    private ?int $originalSize = null;

    public function getOriginalSize(): ?int
    {
        return $this->originalSize;
    }

    public function setOriginalSize(int $originalSize): static
    {
        $this->originalSize = $originalSize;

        return $this;
    }
}