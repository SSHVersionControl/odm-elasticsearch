<?php

declare(strict_types=1);

namespace CCT\Component\ODMElasticsearch\Transformer;

interface DataTransformerInterface
{
    /**
     * Transforms a value from the original representation to a transformed representation.
     *
     * @param mixed $value The value in the original representation
     *
     * @return mixed The value in the transformed representation
     */
    public function transform($value);

    /**
     * Transforms a value from the transformed representation to its original
     * representation.
     *
     * @param mixed $value The value in the transformed representation
     * @param mixed $object Transform values into this object
     *
     * @return mixed The value in the original representation
     */
    public function reverseTransform($value, $object = null);
}
