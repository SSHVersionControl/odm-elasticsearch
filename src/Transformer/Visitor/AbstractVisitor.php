<?php

declare(strict_types=1);

namespace CCT\Component\ODMElasticsearch\Transformer\Visitor;

use CCT\Component\ODMElasticsearch\Metadata\PropertyMetadata;
use CCT\Component\ODMElasticsearch\Metadata\PropertyMetadataInterface;
use CCT\Component\ODMElasticsearch\Metadata\VirtualPropertyMetadata;
use CCT\Component\ODMElasticsearch\Transformer\DataNavigatorInterface;
use CCT\Component\ODMElasticsearch\Transformer\Exception\RuntimeException;

abstract class AbstractVisitor implements VisitorInterface
{
    /**
     * @var DataNavigatorInterface
     */
    protected $dataNavigator;

    /**
     * Set data navigator for visitor
     *
     * @param DataNavigatorInterface $dataNavigator
     */
    public function setDataNavigator(DataNavigatorInterface $dataNavigator): void
    {
        $this->dataNavigator = $dataNavigator;
    }

    /**
     * Extracts data from an object into an array
     *
     * @param $object
     *
     * @return array|null
     */
    protected function navigateObject($object): ?array
    {
        if (null === $object) {
            return null;
        }

        if (null === $this->dataNavigator) {
            throw new RuntimeException(
                'Data Navigator has not been set. Make sure to call setDataNavigator on visitor before transforming'
            );
        }

        $className = \get_class($object);
        $metadata = $this->dataNavigator->getMetadataForClass($className);

        $dataArray = [];

        /** @var PropertyMetadata|VirtualPropertyMetadata $propertyMetadata */
        foreach ($metadata->getRootClassMetadata()->propertyMetadata as $propertyMetadata) {
            if (!($propertyMetadata instanceof PropertyMetadataInterface)) {
                continue;
            }

            $index = $propertyMetadata->getFieldName() ?? $propertyMetadata->name;

            $value = $propertyMetadata->getValue($object);

            $config = $this->propertyConfig($propertyMetadata, $value);

            $dataArray[$index] = $this->dataNavigator->navigate($value, $this, $config);
        }

        return $dataArray;
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
            $className = $propertyMetadata->getTypeClass() ?? \get_class($value);

            return array('type' => $type, 'class' => $className, 'params' => []);
        }

        return array('type' => $type, 'params' => array());
    }
}
