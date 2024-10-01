<?php 
namespace OSW3\Media\Manager;

use OSW3\Media\Manager\ProviderManager;
use Doctrine\ORM\EntityManagerInterface;

final class EntityManager
{
    private string $classname;
    private bool $unique;
    private bool $update;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProviderManager $providerManager
    ){}

    public function save(string $provider, array $media): ?object
    {
        $config = $this->providerManager->get($provider);
        $options = $config['entity'];

        $this->classname = $options['class'];
        $this->unique    = $options['unique'];
        $this->update    = $options['update'];

        if (!empty($this->classname) && $this->classExists())
        {
            $repository = $this->entityManager->getRepository($this->classname);
            $entity = $repository->findOneBy(['originalFileHash' => $media['source']['md5']]) ?? new $this->classname;

            $this->setProperty($entity, 'originalFileHash', $media['source']['md5']);
            $this->setProperty($entity, 'originalBasename', $media['source']['basename']);
            $this->setProperty($entity, 'originalFilename', $media['source']['filename']);
            $this->setProperty($entity, 'originalMimetype', $media['source']['mimetype']);
            $this->setProperty($entity, 'originalExtension', $media['source']['extension']);
            $this->setProperty($entity, 'originalSize', $media['source']['size']);
            $this->setProperty($entity, 'mediaProvider', $media['provider']);
            $this->setProperty($entity, 'mediaBasename', $media['media']['basename']);
            $this->setProperty($entity, 'mediaFilename', $media['media']['filename']);
            $this->setProperty($entity, 'mediaAliases', $media['aliases']);

            if ($entity->getId() === null) {
                $this->entityManager->persist($entity);
                $this->entityManager->flush();
                return $entity;
            }

            if ($this->unique && $this->update) {
                $this->entityManager->flush();
            }

            return $entity;
        }

        return null;
    }

    private function classExists(): true
    {
        if (!class_exists($this->classname)) {
            throw new \InvalidArgumentException(sprintf('The entity "%s" does not exist.', $this->classname));
        }

        return true;
    }

    private function setProperty($entity, $property, $value)
    {
        if (property_exists($entity, $property)) {
            $setter = "set".ucfirst($property);
            $entity->$setter($value);
        }
    }
}