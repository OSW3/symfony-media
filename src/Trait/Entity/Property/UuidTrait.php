<?php 
namespace OSW3\Media\Trait\Entity\Property;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface as UUID;

trait UuidTrait
{
    #[ORM\Id]
    #[ORM\Column(name: "id", type: "uuid", unique: true)]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\CustomIdGenerator(class: "Ramsey\Uuid\Doctrine\UuidGenerator")]
    private ?uuid $id = null;

    public function getId(): ?uuid
    {
        return $this->id;
    }
}