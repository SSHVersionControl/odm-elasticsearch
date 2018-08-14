<?php

declare(strict_types=1);

namespace CCT\Component\ODMElasticsearch\Transformer\Visitor;

use CCT\Component\ODMElasticsearch\Transformer\DataNavigatorInterface;

interface VisitorInterface
{
    /**
     * Set data navigator for visitor
     *
     * @param DataNavigatorInterface $dataNavigator
     */
    public function setDataNavigator(DataNavigatorInterface $dataNavigator);

    /**
     * Allows visitors to convert the input data to a different representation
     * before the actual formatting process starts.
     *
     * @param mixed $data
     *
     * @return mixed
     */
    public function prepare($data);

    /**
     * @param mixed $data
     * @param array $config
     *
     * @return mixed
     */
    public function visitNull($data, array $config);

    /**
     * @param mixed $data
     * @param array $config
     *
     * @return mixed
     */
    public function visitString($data, array $config);

    /**
     * @param mixed $data
     * @param array $config
     *
     * @return mixed
     */
    public function visitBoolean($data, array $config);

    /**
     * @param mixed $data
     * @param array $config
     *
     * @return mixed
     */
    public function visitDouble($data, array $config);

    /**
     * @param mixed $data
     * @param array $config
     *
     * @return mixed
     */
    public function visitInteger($data, array $config);

    /**
     * @param mixed $data
     * @param array $config
     *
     * @return mixed
     */
    public function visitDate($data, array $config);

    /**
     * @param mixed $data
     * @param array $config
     *
     * @return mixed
     */
    public function visitDateTime($data, array $config);

    /**
     * @param mixed $data
     * @param array $config
     *
     * @return mixed
     */
    public function visitTime($data, array $config);

    /**
     * @param mixed $data
     * @param array $config
     *
     * @return mixed
     */
    public function visitArray(array $data, array $config);

    /**
     * @param mixed $data
     * @param array $config
     *
     * @return mixed
     */
    public function visitObject($data, array $config);
}
