<?php

declare(strict_types=1);

namespace CCT\Component\ORMElasticsearch\Transformer\Visitor;

use DateTime;

class ReverseElasticsearchVisitor extends AbstractReverseVisitor
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
     * @param array $config
     *
     * @return mixed|null
     */
    public function visitNull($data, array $config)
    {
        return null;
    }

    /**
     * @param mixed $data
     * @param array $config
     *
     * @return mixed|string
     */
    public function visitString($data, array $config)
    {
        return (string)$data;
    }

    /**
     * @param mixed $data
     * @param array $config
     *
     * @return bool|mixed
     */
    public function visitBoolean($data, array $config)
    {
        return (bool)$data;
    }

    public function visitInteger($data, array $config)
    {
        return (int)$data;
    }

    public function visitDouble($data, array $config)
    {
        return (float)$data;
    }

    public function visitDate($data, array $config)
    {
        return new DateTime($data);
    }

    /**
     * @param mixed $data
     * @param array $config
     *
     * @return mixed
     */
    public function visitDateTime($data, array $config)
    {
        return new DateTime($data);
    }

    /**
     * @param mixed $data
     * @param array $config
     *
     * @return mixed
     */
    public function visitTime($data, array $config)
    {
        $inSeconds = (int)$data;

        $hours = floor($inSeconds / 3600);
        $minutes = floor($inSeconds / 60 % 60);
        $seconds = floor($inSeconds % 60);

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    /**
     * @param array $data
     * @param array $config
     *
     * @return mixed
     */
    public function visitArray(array $data, array $config)
    {
        $visitedArray = [];
        foreach ($data as $index => $item) {
            //Navigate over items again
            $itemConfig = null;
            if (isset($config['class'])) {
                $itemConfig = ['type' => 'object', 'params' => [], 'class' => $config['class']];
            }
            $visitedArray[$index] = $this->dataNavigator->navigate($item, $this, $itemConfig);
        }

        return $visitedArray;
    }

    public function visitObject($data, array $config)
    {
        return $this->navigateObjectHydrate($data, $config);
    }
}
