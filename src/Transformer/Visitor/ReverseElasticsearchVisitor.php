<?php

declare(strict_types=1);

namespace CCT\Component\ORMElasticsearch\Transformer\Visitor;

use DateTime;

class ReverseElasticsearchVisitor implements ReverseVisitorInterface
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
        return new DateTime($data);
    }

    /**
     * @param mixed $data
     * @param array $type
     *
     * @return mixed
     */
    public function visitDateTime($data, array $type)
    {
        return new DateTime($data);
    }

    /**
     * @param mixed $data
     * @param array $type
     *
     * @return mixed
     */
    public function visitTime($data, array $type)
    {
        $inSeconds = (int)$data;

        $hours = floor($inSeconds / 3600);
        $minutes = floor($inSeconds / 60 % 60);
        $seconds = floor($inSeconds % 60);

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    /**
     * @param array $data
     * @param array $type
     *
     * @return mixed
     */
    public function visitArray(array $data, array $type)
    {
//        foreach($data as $item){
//            //Navigate over items again
//        }
        return $data;
    }
}
