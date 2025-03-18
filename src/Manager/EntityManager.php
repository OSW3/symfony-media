<?php 
namespace OSW3\Media\Manager;

use OSW3\Media\Manager\ProviderManager;
use Doctrine\ORM\EntityManagerInterface;

final class EntityManager
{
    private string $entity;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProviderManager $providerManager,
        private StorageManager $storageManager,
    ){}

    public function save(array $media): object
    {
        $classname         = $media['provider']->entity;
        $allowUpdate       = $media['provider']->allowUpdate;

        if (!class_exists($classname)) {
            throw new \InvalidArgumentException(sprintf('The entity "%s" does not exist.', $classname));
        }

        $repository = $this->entityManager->getRepository($classname);

        $entity = $repository->findOneBy(['originalFileHash' => $media['source']->md5]) ?? new $classname;
        $clone  = clone($entity);
        $isNew  = !$entity->getId();

        $this->setProperty($entity, 'originalFileHash', $media['source']->md5);
        $this->setProperty($entity, 'originalBasename', $media['source']->basename);
        $this->setProperty($entity, 'originalFilename', $media['source']->filename);
        $this->setProperty($entity, 'originalMimetype', $media['source']->mimetype);
        $this->setProperty($entity, 'originalExtension',$media['source']->extension);
        $this->setProperty($entity, 'originalSize',     $media['source']->size);
        $this->setProperty($entity, 'mediaProvider',    $media['provider']->name);
        $this->setProperty($entity, 'mediaBasename',    $media['temp']->basename);
        $this->setProperty($entity, 'mediaFilename',    $media['temp']->filename);
        $this->setProperty($entity, 'mediaAliases',     $media['aliases']);


        if ($isNew) {
            $this->entityManager->persist($entity);
            $this->entityManager->flush();
            return $entity;
        }

        if ($allowUpdate) {
            $this->entityManager->flush();
            if ($entity->getMediaBasename() !== $clone->getMediaBasename()) {
                $this->storageManager->removeFromEntity($clone);
            }
        }

        return $entity;
    }

    private function setProperty($entity, $property, $value)
    {
        $setter = "set".ucfirst($property);
        
        if (method_exists($entity, $setter)) {
            $entity->$setter($value);
        }
    }
}