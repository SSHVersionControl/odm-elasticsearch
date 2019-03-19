<?php

declare(strict_types=1);

namespace CCT\Component\ODMElasticsearch\Repository;

use CCT\Component\ODMElasticsearch\Metadata\ClassMetadata;
use CCT\Component\ODMElasticsearch\Metadata\PropertyMetadataInterface;
use CCT\Component\ODMElasticsearch\Repository\Exception\NoMetadataConfigException;
use Elastica\Client;
use Elastica\Type\Mapping;
use Metadata\ClassHierarchyMetadata;
use Metadata\MetadataFactory;

class IndexMapping implements IndexMappingInterface
{
    /**
     * Elastic search type. To be removed in future versions of elastic search
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/master/removal-of-types.html
     * @var string
     */
    protected $type = 'record';

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var MetadataFactory
     */
    protected $metadataFactory;

    /**
     * @var bool
     */
    protected $testEnvironment;

    /**
     * IndexMapping constructor.
     *
     * @param Client $client
     * @param MetadataFactory $metadataFactory
     * @param bool $testEnvironment
     */
    public function __construct(Client $client, MetadataFactory $metadataFactory, bool $testEnvironment = false)
    {
        $this->client = $client;
        $this->metadataFactory = $metadataFactory;
        $this->testEnvironment = $testEnvironment;
    }

    /**
     * Get Elastica Index for specific entity. If index does not exists, it creates one.
     * If mapping is change then its updated
     *
     * @param string $entityName
     *
     * @return \CCT\Component\ODMElasticsearch\Repository\Index
     * @throws \Exception
     */
    public function getIndex(string $entityName): Index
    {
        /** @var ClassHierarchyMetadata $classMetadata */
        $metadata = $this->getMetaData($entityName);

        /** @var ClassMetadata $classMetadata */
        $classMetadata = $metadata->getRootClassMetadata();

        if (null === $classMetadata->getIndex()) {
            throw new NoMetadataConfigException(
                sprintf('Metadata is missing index configuration for "%s"', $entityName)
            );
        }
        $indexConfig = $classMetadata->getIndex();

        if (true === $this->testEnvironment) {
            $indexConfig['name'] .= '_test';
        }

        $index = new Index($this->client, $indexConfig['name']);
        $mappingConfig = $this->extractMappingConfig($metadata);

        if (false === $index->exists()) {
            $this->createIndex($index, $indexConfig);
            $this->defineMapping($index, $mappingConfig);

            return $index;
        }

        $mappingDiff = $this->getMappingDifference($index, $mappingConfig);

        //If diff the update mapping
        if (\count($mappingDiff) > 0) {
            throw new \RuntimeException('System does not yet support dynamic updates to index mapping yet!');
        }

        return $index;
    }

    /**
     * @param string $entityName
     *
     * @return Index|null
     */
    public function getIndexAlias(string $entityName): ?Index
    {
        $metadata = $this->getMetaData($entityName);

        /** @var ClassMetadata $classMetadata */
        $classMetadata = $metadata->getRootClassMetadata();

        if (null === $classMetadata->getIndex()) {
            throw new NoMetadataConfigException(
                sprintf('Metadata is missing index configuration for "%s"', $entityName)
            );
        }

        $indexConfig = $classMetadata->getIndex();

        if (false === isset($indexConfig['alias'])) {
            return null;
        }

        if (true === $this->testEnvironment) {
            $indexConfig['alias'] .= '_test';
        }

        return new Index($this->client, $indexConfig['alias']);
    }

    /**
     * Creates the mapping on elastic search
     *
     * @param Index $index
     * @param array $mappingConfig
     *
     * @throws \Exception
     */
    protected function defineMapping(Index $index, array $mappingConfig): void
    {
        //Create a type
        $type = $index->getType();

        // Define mapping
        $mapping = new Mapping();
        $mapping->setType($type);

        $mapping->setProperties($mappingConfig);

        $response = $mapping->send();

        if (false === $response->isOk()) {
            throw new \RuntimeException($response->getErrorMessage());
        }
    }

    /**
     * Creates index on elastic search
     *
     * @param Index $index
     * @param array $indexConfig
     */
    protected function createIndex(Index $index, array $indexConfig): void
    {
        $response = $index->create($indexConfig['settings']);
        if (false === $response->isOk()) {
            throw new \RuntimeException($response->getErrorMessage());
        }

        if (false === isset($indexConfig['alias'])) {
            return;
        }

        $aliasResponse = $index->addAlias($indexConfig['alias']);

        if (false === $aliasResponse->isOk()) {
            throw new \RuntimeException($aliasResponse->getErrorMessage());
        }
    }

    /**
     * Gets the differences between local index mapping and elastic search mapping
     *
     * @param Index $index
     * @param array $mappingConfig
     *
     * @return array
     * @throws \Exception
     */
    protected function getMappingDifference(Index $index, array $mappingConfig): array
    {
        $mapping = $index->getMapping();

        $elasticMapping = $mapping[$this->type]['properties'] ?? [];

        return $this->getDifferenceBetweenMultiArray($mappingConfig, $elasticMapping);
    }

    /**
     * Extract mapping data for elastic search from class metadata
     *
     * @param ClassHierarchyMetadata $classMetadata
     *
     * @return array
     */
    public function extractMappingConfig(ClassHierarchyMetadata $classMetadata): array
    {
        $mappingConfig = [];

        foreach ($classMetadata->getRootClassMetadata()->propertyMetadata as $propertyMetadata) {
            if (!($propertyMetadata instanceof PropertyMetadataInterface)) {
                continue;
            }

            $index = $propertyMetadata->getFieldName() ?? $propertyMetadata->name;

            if ('object' === $propertyMetadata->getType()) {
                $className = $propertyMetadata->getTypeClass();

                $subClassMetadata = $this->metadataFactory->getMetadataForClass($className);

                if (null === $subClassMetadata) {
                    throw new NoMetadataConfigException(
                        sprintf('No metadata config was found for sub class, "%s"', $className)
                    );
                }
                $mappingConfig[$index] = [
                    'type' => 'object',
                    'properties' => $this->extractMappingConfig($subClassMetadata)
                ];
                continue;
            }

            if (null === $propertyMetadata->getMapping()) {
                continue;
            }

            $mappingConfig[$index] = $propertyMetadata->getMapping();
        }

        return $mappingConfig;
    }

    /**
     * Returns array of differences in a multi dimensional array, otherwise an empty array.
     * Does not take into account ordering of indexes.
     *
     * @param $array1
     * @param $array2
     *
     * @return array
     */
    protected function getDifferenceBetweenMultiArray($array1, $array2): array
    {
        $difference = [];

        foreach ($array1 as $key => $value) {
            if ($value === 'object') {
                continue;
            }

            if (!array_key_exists($key, $array2)) {
                $difference[$key] = $value;
                continue;
            }

            if (\is_array($value)) {
                $arrayRecursiveDiff = $this->getDifferenceBetweenMultiArray($value, $array2[$key]);
                if (count($arrayRecursiveDiff)) {
                    $difference[$key] = $arrayRecursiveDiff;
                }
            } elseif ($value !== $array2[$key]) {
                $difference[$key] = $value;
            }
        }

        return $difference;
    }

    /**
     * @param string $entityName
     *
     * @return ClassHierarchyMetadata|\Metadata\MergeableClassMetadata|null
     */
    private function getMetaData(string $entityName)
    {
        $metadata = $this->metadataFactory->getMetadataForClass($entityName);

        if (null === $metadata) {
            throw new NoMetadataConfigException(
                sprintf('No metadata config was found for "%s"', $entityName)
            );
        }

        return $metadata;
    }
}
