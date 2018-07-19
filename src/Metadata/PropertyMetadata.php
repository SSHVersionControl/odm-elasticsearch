<?php

declare(strict_types=1);

namespace CCT\Component\ORMElasticsearch\Metadata;

use Metadata\PropertyMetadata as BasePropertyMetadata;

class PropertyMetadata extends BasePropertyMetadata implements PropertyMetadataInterface
{
    /**
     * @var boolean
     */
    protected $isExposed;

    /**
     * @var string|null
     */
    protected $fieldName;

    /**
     * Type of the property
     *
     * @var string|null
     */
    protected $type;

    /**
     * The object class name. Only set when type set to object
     *
     * @var string|null
     */
    protected $typeClass;

    /**
     * Getter function name
     *
     * @var string | null
     */
    protected $getter;

    /**
     * Setter function name
     *
     * @var string | null
     */
    protected $setter;

    /**
     * Elasticsearch mapping config for property e.g.
     *  [
     *  'type' => 'integer',
     *  'normalizer' => 'lowercasing'
     * ]
     *
     * @var array | null
     */
    protected $mapping;

    /**
     * @return bool
     */
    public function isExposed(): bool
    {
        return $this->isExposed;
    }

    /**
     * @param bool $isExposed
     */
    public function setIsExposed(bool $isExposed): void
    {
        $this->isExposed = $isExposed;
    }

    /**
     * @return string|null
     */
    public function getFieldName(): ?string
    {
        return $this->fieldName;
    }

    /**
     * @param string|null $fieldName
     */
    public function setFieldName(string $fieldName = null): void
    {
        $this->fieldName = $fieldName;
    }

    /**
     * @return null|string
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param null|string $type
     */
    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    /**
     * Set property setter function
     *
     * @param null $setter
     */
    public function setSetterAccessor($setter = null): void
    {
        $this->setter = $setter;
    }

    /**
     * Set property getter function
     *
     * @param null $getter
     */
    public function setGetterAccessor($getter = null): void
    {
        $this->getter = $getter;
    }

    /**
     * Sets default setter based on property name appended with "set" eg setName
     */
    public function setDefaultSetterAccessor()
    {
        $class = $this->reflection->getDeclaringClass();

        $methodName = ucfirst($this->name);

        if ($class->hasMethod('set' . $methodName) && $class->getMethod('set' . $methodName)->isPublic()) {
            $this->setter = 'set' . $methodName;
        }
    }

    /**
     * Sets default getter based on property name appended with "get", "has", or "is" eg getName, hasName, isName
     */
    public function setDefaultGetterAccessor()
    {
        $class = $this->reflection->getDeclaringClass();

        $methodName = ucfirst($this->name);

        if ($class->hasMethod('get' . $methodName) && $class->getMethod('get' . $methodName)->isPublic()) {
            $this->getter = 'get' . $methodName;

            return;
        }
        if ($class->hasMethod('is' . $methodName) && $class->getMethod('is' . $methodName)->isPublic()) {
            $this->getter = 'is' . $methodName;

            return;
        }
        if ($class->hasMethod('has' . $methodName) && $class->getMethod('has' . $methodName)->isPublic()) {
            $this->getter = 'has' . $methodName;

            return;
        }
    }

    /**
     * @param object $obj
     *
     * @return mixed
     */
    public function getValue($obj)
    {
        if (null === $this->getter) {
            return parent::getValue($obj);
        }

        return $obj->{$this->getter}();
    }

    /**
     * @param object $obj
     * @param string $value
     */
    public function setValue($obj, $value)
    {
        if (null === $this->setter) {
            parent::setValue($obj, $value);

            return;
        }

        $obj->{$this->setter}($value);
    }

    /**
     * @return null|string
     */
    public function getTypeClass(): ?string
    {
        return $this->typeClass;
    }

    /**
     * @param null|string $typeClass
     */
    public function setTypeClass(?string $typeClass): void
    {
        $this->typeClass = $typeClass;
    }

    /**
     * @return array|null
     */
    public function getMapping(): ?array
    {
        return $this->mapping;
    }

    /**
     * @param array|null $mapping
     */
    public function setMapping(?array $mapping): void
    {
        $this->mapping = $mapping;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}
