<?php 
namespace OSW3\Media\Trait\Entity\Property;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait OriginalFileHashTrait
{
    #[ORM\Column(name: 'original_file_hash', type: Types::STRING, length: 32, nullable: false, unique: true, options: ['fixed' => true])]
    private ?string $originalFileHash = null;

    public function setOriginalFileHash(string $originalFileHash): static
    {
        $this->originalFileHash = $originalFileHash;

        return $this;
    }

    public function getOriginalFileHash(): ?string
    {
        return $this->originalFileHash;
    }
}