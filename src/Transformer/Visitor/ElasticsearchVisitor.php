<?php

declare(strict_types=1);

namespace CCT\Component\ODMElasticsearch\Transformer\Visitor;

class ElasticsearchVisitor extends AbstractVisitor
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
        $timestamp = 0;
        if ($data instanceof \DateTimeInterface) {
            $timestamp = (int) substr($data->format('Uu'), 0, 13);
            return $timestamp;
        }

        if (\is_string($data)) {
            $timestamp = strtotime($data);
        }

        return round($timestamp * 1000);
    }

    /**
     * @param mixed $data
     * @param array $config
     *
     * @return mixed
     */
    public function visitDateTime($data, array $config)
    {
        return $this->visitDate($data, $config);
    }

    /**
     * @param mixed $data
     * @param array $config
     *
     * @return mixed
     */
    public function visitTime($data, array $config)
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
            if (isset($config['type_class'])) {
                $itemConfig = ['type' => 'object', 'params' => [], 'type_class', 'type_class'];
            }
            $visitedArray[$index] = $this->dataNavigator->navigate($item, $this, $itemConfig);
        }

        return $visitedArray;
    }

    public function visitObject($data, array $config)
    {
        return $this->navigateObject($data);
    }
}
