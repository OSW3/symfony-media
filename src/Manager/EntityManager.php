<?php 
namespace OSW3\Media\Manager;

use OSW3\Media\Manager\ProviderManager;
use Doctrine\ORM\EntityManagerInterface;

final class EntityManager
{
    private string $entity;
    // private bool $allowUpdate;
    // private bool $allowDelete;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProviderManager $providerManager
    ){}

    // public function save(string $provider, array $media) //: ?object
    public function save(array $media): object
    {
        $classname   = $media['provider']->entity;
        $isUnique    = $media['provider']->unique;
        $allowUpdate = $media['provider']->allowUpdate;

        $originalFileHash  = $media['source']->md5;
        $originalBasename  = $media['source']->basename;
        $originalFilename  = $media['source']->filename;
        $originalMimetype  = $media['source']->mimetype;
        $originalExtension = $media['source']->extension;
        $originalSize      = $media['source']->size;
        $mediaProvider     = $media['provider']->name;
        $mediaBasename     = $media['temp']->basename;
        $mediaFilename     = $media['temp']->filename;
        $mediaAliases      = $media['aliases'];

        if (!class_exists($classname)) {
            throw new \InvalidArgumentException(sprintf('The entity "%s" does not exist.', $classname));
        }

        $repository = $this->entityManager->getRepository($classname);
        $entity     = $repository->findOneBy(['originalFileHash' => $media['source']->md5]) ?? new $classname;

        $this->setProperty($entity, 'originalFileHash', $originalFileHash);
        $this->setProperty($entity, 'originalBasename', $originalBasename);
        $this->setProperty($entity, 'originalFilename', $originalFilename);
        $this->setProperty($entity, 'originalMimetype', $originalMimetype);
        $this->setProperty($entity, 'originalExtension', $originalExtension);
        $this->setProperty($entity, 'originalSize', $originalSize);
        $this->setProperty($entity, 'mediaProvider', $mediaProvider);
        $this->setProperty($entity, 'mediaBasename', $mediaBasename);
        $this->setProperty($entity, 'mediaFilename', $mediaFilename);
        $this->setProperty($entity, 'mediaAliases', $mediaAliases);

        // New Entity
        if ($entity->getId() === null) {
            $this->entityManager->persist($entity);
            $this->entityManager->flush();
            return $entity;
        }

        if ($isUnique && $allowUpdate) {
            $this->entityManager->flush();
        }
        
        return $entity;
    }

    private function setProperty($entity, $property, $value)
    {
        if (property_exists($entity, $property)) {
            $setter = "set".ucfirst($property);
            $entity->$setter($value);
        }
    }
}