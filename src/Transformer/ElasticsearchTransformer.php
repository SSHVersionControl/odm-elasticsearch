<?php

declare(strict_types=1);

namespace CCT\Component\ORMElasticsearch\Transformer;

use CCT\Component\ORMElasticsearch\Transformer\Exception\TransformationFailedException;
use CCT\Component\ORMElasticsearch\Transformer\Visitor\ReverseVisitorInterface;
use CCT\Component\ORMElasticsearch\Transformer\Visitor\VisitorInterface;

class ElasticsearchTransformer implements DataTransformerInterface
{
    /**
     * @var DataNavigatorInterface
     */
    protected $dataNavigator;

    /**
     * @var VisitorInterface
     */
    protected $visitor;

    /**
     * @var ReverseVisitorInterface
     */
    protected $reverseVisitor;

    /**
     * ElasticsearchTransformer constructor.
     *
     * @param DataNavigatorInterface $dataNavigator
     * @param VisitorInterface $visitor
     * @param ReverseVisitorInterface $reverseVisitor
     */
    public function __construct(
        DataNavigatorInterface $dataNavigator,
        VisitorInterface $visitor,
        ReverseVisitorInterface $reverseVisitor
    ) {
        $this->dataNavigator = $dataNavigator;
        $this->visitor = $visitor;
        $this->reverseVisitor = $reverseVisitor;
    }

    /**
     * Transforms a value from the original representation to a transformed representation.
     *
     * @param mixed $value The value in the original representation
     *
     * @return mixed The value in the transformed representation
     * @throws TransformationFailedException When the transformation fails.
     */
    public function transform($value)
    {
        $this->visitor->setDataNavigator($this->dataNavigator);
        return $this->dataNavigator->navigate($value, $this->visitor, null);
    }

    /**
     * Transforms a value from the transformed representation to its original
     * representation.
     *
     * @param mixed $value The value in the transformed representation
     * @param object $object Transform values into this object
     *
     * @return mixed The value in the original representation
     * @throws TransformationFailedException When the transformation fails.
     */
    public function reverseTransform($value, $object = null)
    {
        if (null === $object || false === \is_object($object)) {
            throw new TransformationFailedException('You must pass an object to the reverseTransform');
        }

        $config = array(
            'type' => 'object',
            'class' => \get_class($object),
            'params' => [
                'populate_object' => $object
            ]
        );

        $this->reverseVisitor->setDataNavigator($this->dataNavigator);

        return $this->dataNavigator->navigate($value, $this->reverseVisitor, $config);
    }
}
