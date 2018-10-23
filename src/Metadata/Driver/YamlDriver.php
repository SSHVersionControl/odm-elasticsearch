<?php

declare(strict_types=1);

namespace CCT\Component\ODMElasticsearch\Metadata\Driver;

use CCT\Component\ODMElasticsearch\Metadata\ClassMetadata;
use CCT\Component\ODMElasticsearch\Metadata\Exception\InvalidArgumentException;
use CCT\Component\ODMElasticsearch\Metadata\PropertyMetadataInterface;
use CCT\Component\ODMElasticsearch\Metadata\VirtualPropertyMetadata;
use Metadata\Driver\AbstractFileDriver;

use CCT\Component\ODMElasticsearch\Metadata\PropertyMetadata;
use Symfony\Component\Yaml\Yaml;

class YamlDriver extends AbstractFileDriver
{
    /**
     * Load metadata from config file
     *
     * @param \ReflectionClass $class
     * @param string $file
     *
     * @return ClassMetadata|\Metadata\ClassMetadata|null
     */
    protected function loadMetadataFromFile(\ReflectionClass $class, $file)
    {
        $config = Yaml::parse(file_get_contents($file));

        $className = $class->name;

        if (!isset($config[$className])) {
            throw new \RuntimeException(
                sprintf('Expected metadata for class %s to be defined in %s.', $class->name, $file)
            );
        }

        $classConfig = $config[$className];
        $metadata = new ClassMetadata($className);

        if (array_key_exists('index', $classConfig)) {
            $metadata->setIndex($classConfig['index']);
        }

        if (array_key_exists('customRepositoryName', $classConfig)) {
            $metadata->setCustomRepositoryName($classConfig['customRepositoryName']);
        }

        $this->processProperties($class, $metadata, $classConfig);

        $this->processVirtualProperties($class, $metadata, $classConfig);

        return $metadata;
    }

    /**
     * File extension type to look for in config folder. All other extensions will be ignored
     *
     * @return string
     */
    protected function getExtension()
    {
        return 'yaml';
    }

    /**
     * Process properties
     *
     * @param \ReflectionClass $class
     * @param \Metadata\ClassMetadata $metadata
     * @param array $classConfig
     */
    protected function processProperties(
        \ReflectionClass $class,
        \Metadata\ClassMetadata $metadata,
        array $classConfig
    ): void {
        $className = $class->name;
        $exposeAll = $classConfig['exposeAll'] ?? true;

        $classParent = null;
        if($class->getParentClass()){
            $classParent = $class->getParentClass()->name;
        }

        foreach ($class->getProperties() as $property) {
            if (($property->class !== $className && $property->class !== $classParent)
                || (isset($property->info) && $property->info['class'] !== $className)
            ) {
                continue;
            }

            $propertyName = $property->getName();
            $propertyConfig = $classConfig['properties'][$propertyName] ?? null;

            if (null === $propertyConfig && false === $exposeAll) {
                continue;
            }

            $propertyConfig['use_default_accessors'] = $classConfig['use_default_accessors'] ?? false;

            if (isset($propertyConfig['expose']) && false === $propertyConfig['expose']) {
                continue;
            }

            $propertyMetadata = new PropertyMetadata($className, $propertyName);

            $this->applyPropertyConfigToPropertyMetadata($propertyMetadata, $propertyConfig);

            //Fall back to get property type from annotation @var
            if (null === $propertyMetadata->getType()) {
                $type = $this->getPropertyTypeFromAnnotation($property);
                $propertyMetadata->setType($type);
            }

            $metadata->addPropertyMetadata($propertyMetadata);
        }
    }

    /**
     * Process virtual property metadata
     *
     * @param \ReflectionClass $class
     * @param \Metadata\ClassMetadata $metadata
     * @param array $classConfig
     */
    protected function processVirtualProperties(
        \ReflectionClass $class,
        \Metadata\ClassMetadata $metadata,
        array $classConfig
    ): void {
        if (!array_key_exists('virtual_properties', $classConfig)) {
            return;
        }
        $className = $class->name;

        foreach ($classConfig['virtual_properties'] as $methodName => $virtualPropertyConfig) {
            if (!$class->hasMethod($methodName)) {
                throw new InvalidArgumentException('The method ' . $methodName . ' not found in class ' . $className);
            }

            $virtualPropertyConfig['use_default_accessors'] = false;
            $virtualPropertyMetadata = new VirtualPropertyMetadata($className, $methodName);

            $this->applyPropertyConfigToPropertyMetadata($virtualPropertyMetadata, $virtualPropertyConfig);

            $metadata->addPropertyMetadata($virtualPropertyMetadata);
        }
    }

    /**
     * Apply config to a property metadata
     *
     * @param PropertyMetadataInterface|PropertyMetadata $propertyMetadata
     * @param array $propertyConfig
     */
    protected function applyPropertyConfigToPropertyMetadata(
        PropertyMetadataInterface $propertyMetadata,
        array $propertyConfig
    ): void {
        // Set index name
        if (isset($propertyConfig['field_name'])) {
            $propertyMetadata->setFieldName($propertyConfig['field_name']);
        }

        // Set property type
        if (isset($propertyConfig['type'])) {
            $propertyMetadata->setType((string)$propertyConfig['type']);

            if (isset($propertyConfig['type_class'])) {
                $propertyMetadata->setTypeClass((string)$propertyConfig['type_class']);
            }
        }

        // Set accessors
        if (true === $propertyConfig['use_default_accessors']) {
            $propertyMetadata->setDefaultGetterAccessor();
            $propertyMetadata->setDefaultSetterAccessor();
        }

        if (isset($propertyConfig['accessor']['getter'])) {
            $propertyMetadata->setGetterAccessor($propertyConfig['accessor']['getter']);
        }

        if (isset($propertyConfig['accessor']['setter'])) {
            $propertyMetadata->setSetterAccessor($propertyConfig['accessor']['setter']);
        }

        // Set Elastic Search Mapping
        if (isset($propertyConfig['mapping'])) {
            $propertyMetadata->setMapping($propertyConfig['mapping']);
        }
    }

    /**
     * Try get the property type from annotation @var. This is very unreliable
     *
     * @param \ReflectionProperty $property
     *
     * @return null| string
     */
    protected function getPropertyTypeFromAnnotation(\ReflectionProperty $property): ?string
    {
        if (false === $property->getDocComment()) {
            return null;
        }

        if (preg_match('/@var\s+([^\s]+)/', $property->getDocComment(), $matches)) {
            [, $type] = $matches;

            if (null === $type || $type === 'resource') {
                return null;
            }

            if (\in_array($type, ['string', 'integer', 'int', 'float', 'boolean', 'bool', 'array'])) {
                return $type;
            }

            return 'object';
        }

        return null;
    }
}
