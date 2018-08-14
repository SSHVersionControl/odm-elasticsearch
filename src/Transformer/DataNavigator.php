<?php

declare(strict_types=1);

namespace CCT\Component\ODMElasticsearch\Transformer;

use CCT\Component\ODMElasticsearch\Transformer\Exception\NoMetadataException;
use CCT\Component\ODMElasticsearch\Transformer\Visitor\VisitorInterface;
use Metadata\MetadataFactory;
use Metadata\MetadataFactoryInterface;

class DataNavigator implements DataNavigatorInterface
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

    /**
     * Applies the visitor pattern to the data
     *
     * @param mixed $data
     * @param VisitorInterface $visitor
     * @param array|null $config
     *
     * @return array|mixed|null
     */
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
                return $visitor->visitArray((array)$data, $config);
            case 'object':
                return $visitor->visitObject($data, $config);
        }

        return null;
    }

    /**
     * Tries to guess the configuration based on VALUE type
     *
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
     * @param $className
     *
     * @return \Metadata\ClassHierarchyMetadata|\Metadata\MergeableClassMetadata
     *
     * @throws NoMetadataException
     */
    public function getMetadataForClass($className)
    {
        $metadata = $this->metadataFactory->getMetadataForClass($className);

        if (null === $metadata) {
            throw new NoMetadataException(
                sprintf('No metadata was found for class "%s". Please check configuration', $className)
            );
        }

        return $metadata;
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
