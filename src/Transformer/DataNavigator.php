<?php

declare(strict_types=1);

namespace CCT\Component\ORMElasticsearch\Transformer;

use CCT\Component\ORMElasticsearch\Metadata\Exception\InvalidArgumentException;
use CCT\Component\ORMElasticsearch\Metadata\PropertyMetadata;
use CCT\Component\ORMElasticsearch\Metadata\PropertyMetadataInterface;
use CCT\Component\ORMElasticsearch\Metadata\VirtualPropertyMetadata;
use CCT\Component\ORMElasticsearch\Transformer\Exception\NoMetadataException;
use CCT\Component\ORMElasticsearch\Transformer\Visitor\ReverseVisitorInterface;
use CCT\Component\ORMElasticsearch\Transformer\Visitor\VisitorInterface;
use Metadata\MetadataFactory;
use Metadata\MetadataFactoryInterface;

class DataNavigator
{
    /**
     * @var MetadataFactoryInterface|MetadataFactory
     */
    protected $metadataFactory;

    /**
     * DataNavigator constructor.
     *
     * @param MetadataFactoryInterface $metadataFactory
     */
    public function __construct(MetadataFactoryInterface $metadataFactory)
    {
        $this->metadataFactory = $metadataFactory;
    }

    /**
     * @param mixed $data
     * @param VisitorInterface $visitor
     * @param array|null $config format of array is ['type' => ]
     *
     * @return mixed
     */
    public function navigate($data, VisitorInterface $visitor, array $config = null)
    {

        // If the type was not given, we infer the most specific type from the
        // input data in serialization mode.
        if (null === $config) {
            $config = $this->suggestedConfig($data);
        }

        return $this->applyVisitorToData($data, $visitor, $config);
    }

    protected function applyVisitorToData($data, VisitorInterface $visitor, array $config = null)
    {

        if (!isset($config['type'])) {
            throw new \RuntimeException('Config type not set for data navigator');
        }
        switch ($config['type']) {
            case 'NULL':
                return $visitor->visitNull($data, $config);

            case 'string':
                return $visitor->visitString($data, $config);

            case 'int':
            case 'integer':
                return $visitor->visitInteger($data, $config);

            case 'bool':
            case 'boolean':
                return $visitor->visitBoolean($data, $config);

            case 'double':
            case 'float':
                return $visitor->visitDouble($data, $config);
            case 'date':
                return $visitor->visitDate($data, $config);
            case 'dateTime':
            case 'datetime':
                return $visitor->visitDateTime($data, $config);
            case 'time':
                return $visitor->visitTime($data, $config);
            case 'array':
                return $visitor->visitArray($data, $config);
            case 'object':
                if ($visitor instanceof ReverseVisitorInterface) {
                    return $this->navigateObjectPopulate($data, $visitor, $config);
                }

                return $this->navigateObject($data, $visitor);
        }

        return null;
    }

    /**
     * Extracts data from an object into an array
     *
     * @param $object
     * @param VisitorInterface $visitor
     *
     * @return array
     */
    protected function navigateObject($object, VisitorInterface $visitor): array
    {
        if (null === $object) {
            return null;
        }

        $className = \get_class($object);
        $metadata = $this->metadataFactory->getMetadataForClass($className);

        if (null === $metadata) {
            throw new NoMetadataException(
                sprintf('No metadata was found for class "%s". Please check configuration', $className)
            );
        }

        $dataArray = [];

        /** @var PropertyMetadata|VirtualPropertyMetadata $propertyMetadata */
        foreach ($metadata->getRootClassMetadata()->propertyMetadata as $propertyMetadata) {
            if (!($propertyMetadata instanceof PropertyMetadataInterface)) {
                continue;
            }

            $index = $propertyMetadata->getFieldName() ?? $propertyMetadata->name;

            $value = $propertyMetadata->getValue($object);

            $config = $this->propertyConfig($propertyMetadata, $value) ?? $this->suggestedConfig($value);

            $dataArray[$index] = $this->applyVisitorToData($value, $visitor, $config);
        }

        return $dataArray;
    }

    /**
     * Navigate object populating it with data from an array
     *
     * @param array $data
     * @param ReverseVisitorInterface $visitor
     * @param array $config
     *
     * @return mixed
     */
    protected function navigateObjectPopulate(array $data, ReverseVisitorInterface $visitor, array $config)
    {
        if (false === array_key_exists('class', $config)) {
            throw new InvalidArgumentException(
                'Reverse visitors must have the config opinion "class" set for object types'
            );
        }

        $className = $config['class'];
        $metadata = $this->metadataFactory->getMetadataForClass($className);

        $object = $config['params']['populate_object'] ?? new $className;

        /** @var PropertyMetadata|VirtualPropertyMetadata $propertyMetadata */
        foreach ($metadata->getRootClassMetadata()->propertyMetadata as $propertyMetadata) {
            if (!($propertyMetadata instanceof PropertyMetadataInterface)) {
                continue;
            }

            $index = $propertyMetadata->getFieldName() ?? $propertyMetadata->name;

            if (false === array_key_exists($index, $data)) {
                continue;
            }

            $newConfig = $this->getConfigFromPropertyMetadata($propertyMetadata, $object);

            $value = $this->applyVisitorToData($data[$index], $visitor, $newConfig);
            $propertyMetadata->setValue($object, $value);
        }

        return $object;
    }

    /**
     * Get property config for object
     *
     * @param PropertyMetadataInterface|PropertyMetadata|VirtualPropertyMetadata $propertyMetadata
     * @param $value
     *
     * @return array|null
     */
    protected function propertyConfig(PropertyMetadataInterface $propertyMetadata, $value): ?array
    {
        $type = $propertyMetadata->getType();

        if (null === $type) {
            return null;
        }

        if ('object' === $type) {
            $className = \get_class($value);

            return array('type' => $type, 'class' => $className, 'params' => []);
        }

        return array('type' => $type, 'params' => array());
    }

    /**
     * @param $data
     *
     * @return array
     */
    protected function suggestedConfig($data): array
    {
        $typeName = \gettype($data);

        if ($data instanceof \DateTimeInterface) {
            return ['type' => 'datetime', 'params' => []];
        }

        if ('string' === $typeName && $this->isTimeFormat($data)) {
            return ['type' => 'time', 'params' => []];
        }

        if ('object' === $typeName) {
            $className = \get_class($data);

            return ['type' => $typeName, 'class' => $className, 'params' => []];
        }

        return ['type' => $typeName, 'params' => []];
    }

    /**
     * @param PropertyMetadataInterface $propertyMetadata
     * @param mixed $object
     *
     * @return array
     */
    protected function getConfigFromPropertyMetadata(PropertyMetadataInterface $propertyMetadata, $object = null): array
    {
        if (null === $propertyMetadata->getType()) {
            throw new InvalidArgumentException(
                'All properties must have a type set for reverse visit, ' . $propertyMetadata->getName()
            );
        }

        if ('object' === $propertyMetadata->getType()) {
            $className = $propertyMetadata->getTypeClass();
            $params = [];

            if (null !== $object) {
                $relatedObject = $propertyMetadata->getValue($object);
                $params['populate_object'] = $relatedObject ?? null;
            }

            return array('type' => $propertyMetadata->getType(), 'class' => $className, $params);
        }

        return array('type' => $propertyMetadata->getType(), 'params' => array());
    }

    /**
     * Checks if a string is in a time format
     *
     * @param $string
     *
     * @return bool
     */
    protected function isTimeFormat($string): bool
    {
        return (bool)preg_match('/^(?:2[0-3]|[01][0-9]):[0-5][0-9]:[0-5][0-9]$/', $string);
    }
}
