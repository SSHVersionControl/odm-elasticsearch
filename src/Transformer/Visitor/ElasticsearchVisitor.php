<?php

declare(strict_types=1);

namespace CCT\Component\ORMElasticsearch\Transformer\Visitor;

class ElasticsearchVisitor implements VisitorInterface
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
     * @return mixed|null
     */
    public function visitNull($data, array $type)
    {
        return null;
    }

    /**
     * @param mixed $data
     * @param array $type
     *
     * @return mixed|string
     */
    public function visitString($data, array $type)
    {
        return (string)$data;
    }

    /**
     * @param mixed $data
     * @param array $type
     *
     * @return bool|mixed
     */
    public function visitBoolean($data, array $type)
    {
        return (bool)$data;
    }

    public function visitInteger($data, array $type)
    {
        return (int)$data;
    }

    public function visitDouble($data, array $type)
    {
        return (float)$data;
    }

    public function visitDate($data, array $type)
    {
        $timestamp = 0;
        if ($data instanceof \DateTimeInterface) {
            $timestamp = $data->getTimestamp();
        }

        if (\is_string($data)) {
            $timestamp = strtotime($data);
        }

        return round($timestamp * 1000);
    }

    /**
     * @param mixed $data
     * @param array $type
     *
     * @return mixed
     */
    public function visitDateTime($data, array $type)
    {
        return $this->visitDate($data, $type);
    }

    /**
     * @param mixed $data
     * @param array $type
     *
     * @return mixed
     */
    public function visitTime($data, array $type)
    {
        if (null === $data) {
            return 0;
        }
        $parsed = date_parse($data);
        if (false === $parsed) {
            return 0;
        }

        return $parsed['hour'] * 3600 + $parsed['minute'] * 60 + $parsed['second'];
    }

    /**
     * @param array $data
     * @param array $type
     *
     * @return mixed
     */
    public function visitArray(array $data, array $type)
    {
//        foreach ($data as $item) {
//            //Navigate over items again
//        }
        return $data;
    }
}
