<?php

declare(strict_types=1);

namespace CCT\Component\ORMElasticsearch\Transformer\Visitor;

use CCT\Component\ORMElasticsearch\Metadata\Exception\InvalidArgumentException;
use CCT\Component\ORMElasticsearch\Metadata\PropertyMetadata;
use CCT\Component\ORMElasticsearch\Metadata\PropertyMetadataInterface;
use CCT\Component\ORMElasticsearch\Metadata\VirtualPropertyMetadata;
use CCT\Component\ORMElasticsearch\Repository\Exception\NoMetadataConfigException;
use CCT\Component\ORMElasticsearch\Transformer\DataNavigatorInterface;
use CCT\Component\ORMElasticsearch\Transformer\Exception\RuntimeException;

abstract class AbstractReverseVisitor implements ReverseVisitorInterface
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
     * Navigate object populating it with data from an array
     *
     * @param array $data
     * @param array $config
     *
     * @return mixed
     */
    protected function navigateObjectHydrate(array $data, array $config)
    {
        if (false === array_key_exists('class', $config)) {
            throw new InvalidArgumentException(
                'Reverse visitors must have the config opinion "class" set for object types'
            );
        }

        if (null === $this->dataNavigator) {
            throw new RuntimeException(
                'Data Navigator has not been set. Make sure to call setDataNavigator on visitor before transforming'
            );
        }

        $className = $config['class'];
        $metadata = $this->dataNavigator->getMetadataForClass($className);

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

            $value = $this->dataNavigator->navigate($data[$index], $this, $newConfig);
            $propertyMetadata->setValue($object, $value);
        }

        return $object;
    }

    /**
     * Get configuration from property metadata
     *
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
            if (null === $className) {
                throw new NoMetadataConfigException(
                    sprintf(
                        'Property "%s" could not resolve class type. ' .
                        'Please add class_type parameter to property config',
                        $propertyMetadata->getName()
                    )
                );
            }
            $params = [];

            if (null !== $object) {
                $relatedObject = $propertyMetadata->getValue($object);
                $params['populate_object'] = $relatedObject ?? null;
            }

            return array('type' => $propertyMetadata->getType(), 'class' => $className, $params);
        }

        if ('array' === $propertyMetadata->getType()) {
            $className = $propertyMetadata->getTypeClass();
            if (null !== $className) {
                return array('type' => $propertyMetadata->getType(), 'class' => $className, 'params' => array());
            }
        }

        return array('type' => $propertyMetadata->getType(), 'params' => array());
    }
}
