<?php

declare(strict_types=1);

namespace CCT\Component\ODMElasticsearch\Metadata;

use CCT\Component\ODMElasticsearch\Metadata\Exception\InvalidArgumentException;

class VirtualPropertyMetadata extends PropertyMetadata
{
    /**
     * Name of method to call
     *
     * @var string
     */
    protected $methodName;

    /**
     * Virtual property is read only
     *
     * @var bool
     */
    protected $readOnly;

    public function __construct($class, $methodName)
    {
        if (0 === strpos($methodName, 'get')) {
            $fieldName = lcfirst(substr($methodName, 3));
        } else {
            $fieldName = $methodName;
        }

        try {
            parent::__construct($class, $fieldName);
        } catch (\ReflectionException $e) {
            // Horrible hack to call on parent constructor due to vendor lib not using an interface
        }

        $this->getter = $methodName;
        $this->readOnly = true;
    }

    public function setValue($obj, $value)
    {
        throw new \LogicException('VirtualPropertyMetadata is immutable.');
    }

    /**
     * Set property setter function
     *
     * @param null|string $setter
     */
    public function setSetterAccessor($setter = null): void
    {
    }

    /**
     * Set property getter function
     *
     * @param null|string $getter
     */
    public function setGetterAccessor($getter = null): void
    {
    }

    public function getValue($obj)
    {
        if (null === $this->getter) {
            throw new InvalidArgumentException('Getter value has not been set');
        }

        if (false === method_exists($obj, $this->getter)) {
            throw new InvalidArgumentException(
                sprintf('Object does not have a method name called %s', $this->getter)
            );
        }

        return $obj->{$this->getter}();
    }

    public function getAccessor()
    {
        return $this->getter;
    }
}
