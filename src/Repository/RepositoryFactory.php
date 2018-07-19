<?php

declare(strict_types=1);

namespace CCT\Component\ORMElasticsearch\Repository;

use CCT\Component\ORMElasticsearch\Metadata\ClassMetadata;
use CCT\Component\ORMElasticsearch\Repository\Exception\InvalidArgumentException;
use CCT\Component\ORMElasticsearch\Repository\Exception\NoMetadataConfigException;
use CCT\Component\ORMElasticsearch\Repository\Model\DocumentSupportInterface;
use CCT\Component\ORMElasticsearch\Transformer\DataTransformerInterface;
use Metadata\MetadataFactory;

class RepositoryFactory
{
    /**
     * @var IndexMapping
     */
    protected $indexMapping;

    /**
     * @var DataTransformerInterface
     */
    protected $dataTransformer;

    /**
     * @var array
     */
    protected $repositories;

    /**
     * @var MetadataFactory
     */
    protected $metadataFactory;

    /**
     * RepositoryFactory constructor.
     *
     * @param IndexMapping $indexMapping
     * @param DataTransformerInterface $dataTransformer
     * @param MetadataFactory $metadataFactory
     */
    public function __construct(
        IndexMapping $indexMapping,
        DataTransformerInterface $dataTransformer,
        MetadataFactory $metadataFactory
    ) {
        $this->indexMapping = $indexMapping;
        $this->dataTransformer = $dataTransformer;
        $this->repositories = [];
        $this->metadataFactory = $metadataFactory;
    }

    /**
     * Gets repository for entity class name
     *
     * @param string $entityName the entity class name
     *
     * @return AbstractElasticsearchRepository|mixed
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function getRepository(string $entityName)
    {
        $metadata = $this->metadataFactory->getMetadataForClass($entityName);

        if (null === $metadata) {
            throw new NoMetadataConfigException(
                sprintf('No metadata config was found for "%s"', $entityName)
            );
        }

        /** @var ClassMetadata $classMetadata */
        $classMetadata = $metadata->getRootClassMetadata();
        $customRepositoryName = $classMetadata->getCustomRepositoryName();

        $class = new \ReflectionClass($entityName);
        if (!$class->implementsInterface(DocumentSupportInterface::class)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Class "%s", does not implement interface "%s"',
                    $entityName,
                    DocumentSupportInterface::class
                )
            );
        }

        return $this->repositories[$entityName] ?? $this->create($entityName, $customRepositoryName);
    }

    /**
     * @param string $entityName
     * @param string|null $customRepositoryName
     *
     * @return AbstractElasticsearchRepository
     *
     * @throws \Exception
     */
    protected function create(string $entityName, string $customRepositoryName = null): AbstractElasticsearchRepository
    {
        if (null === $customRepositoryName) {
            $repository = new ElasticsearchRepository($entityName, $this->indexMapping, $this->dataTransformer);
            $this->repositories[$entityName] = $repository;

            return $repository;
        }

        $repository = new $customRepositoryName($entityName, $this->indexMapping, $this->dataTransformer);
        $this->repositories[$customRepositoryName] = $repository;

        return $repository;
    }
}
