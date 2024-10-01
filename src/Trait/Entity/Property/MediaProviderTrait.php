<?php 
namespace OSW3\Media\Trait\Entity\Property;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait MediaProviderTrait
{
    #[ORM\Column(name: 'media_provider', type: Types::STRING, length: 80, nullable: false)]
    private ?string $mediaProvider = null;

    public function getMediaProvider(): ?string
    {
        return $this->mediaProvider;
    }

    public function setMediaProvider(string $mediaProvider): static
    {
        $this->mediaProvider = $mediaProvider;

        return $this;
    }
}