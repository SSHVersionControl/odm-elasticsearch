<?php

declare(strict_types=1);

namespace CCT\Component\ORMElasticsearch\Metadata;

interface PropertyMetadataInterface
{
    /**
     * @return string|null
     */
    public function getFieldName(): ?string;

    /**
     * @param string|null $fieldName
     */
    public function setFieldName(string $fieldName = null): void;

    /**
     * @return null|string
     */
    public function getType(): ?string;

    /**
     * @param null|string $type
     */
    public function setType(?string $type): void;

    /**
     * Set property setter function
     *
     * @param null $setter
     */
    public function setSetterAccessor($setter = null): void;
    /**
     * Set property getter function
     *
     * @param null $getter
     */
    public function setGetterAccessor($getter = null): void;

    /**
     * @return null|string
     */
    public function getTypeClass(): ?string;

    /**
     * @param null|string $typeClass
     */
    public function setTypeClass(?string $typeClass): void;

    /**
     * @return array|null
     */
    public function getMapping(): ?array;

    /**
     * @param array|null $mapping
     */
    public function setMapping(?array $mapping): void;

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @param object $obj
     *
     * @return mixed
     */
    public function getValue($obj);

    /**
     * @param object $obj
     * @param mixed $value
     */
    public function setValue($obj, $value);
}
