<?php

declare(strict_types=1);

namespace CCT\Component\ORMElasticsearch\Tests\Fixture\Visitor;

use CCT\Component\ORMElasticsearch\Transformer\Visitor\VisitorInterface;

class FakeVisitor implements VisitorInterface
{
    /**
     * Allows visitors to convert the input data to a different representation
     * before the actual formatting process starts.
     *
     * @param mixed $data
     *
     * @return mixed
     */
    public function prepare($data)
    {
        return $data;
    }

    /**
     * @param mixed $data
     * @param array $type
     *
     * @return mixed
     */
    public function visitNull($data, array $type)
    {
        return $data;
    }

    /**
     * @param mixed $data
     * @param array $type
     *
     * @return mixed
     */
    public function visitString($data, array $type)
    {
        return $data;
    }

    /**
     * @param mixed $data
     * @param array $type
     *
     * @return mixed
     */
    public function visitBoolean($data, array $type)
    {
        return $data;
    }

    /**
     * @param mixed $data
     * @param array $type
     *
     * @return mixed
     */
    public function visitDouble($data, array $type)
    {
        return $data;
    }

    /**
     * @param mixed $data
     * @param array $type
     *
     * @return mixed
     */
    public function visitInteger($data, array $type)
    {
        return $data;
    }

    /**
     * @param mixed $data
     * @param array $type
     *
     * @return mixed
     */
    public function visitDate($data, array $type)
    {
        return $data;
    }

    /**
     * @param mixed $data
     * @param array $type
     *
     * @return mixed
     */
    public function visitDateTime($data, array $type)
    {
        return $data;
    }

    /**
     * @param mixed $data
     * @param array $type
     *
     * @return mixed
     */
    public function visitTime($data, array $type)
    {
        return $data;
    }

    /**
     * @param mixed $data
     * @param array $type
     *
     * @return mixed
     */
    public function visitArray(array $data, array $type)
    {
        return $data;
    }
}
