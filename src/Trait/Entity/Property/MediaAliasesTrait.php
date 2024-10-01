<?php 
namespace OSW3\Media\Trait\Entity\Property;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait MediaAliasesTrait
{
    #[ORM\Column(name: 'media_aliases', type: Types::JSON, nullable: false)]
    private ?array $mediaAliases = [];

    public function getMediaAliases(): ?array
    {
        return $this->mediaAliases;
    }

    public function setMediaAliases(?array $mediaAliases): static
    {
        $this->mediaAliases = $mediaAliases;

        return $this;
    }
}