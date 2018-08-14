<?php
declare(strict_types=1);

namespace CCT\Component\ODMElasticsearch\Transformer;

use CCT\Component\ODMElasticsearch\Transformer\Exception\NoMetadataException;
use CCT\Component\ODMElasticsearch\Transformer\Visitor\VisitorInterface;

interface DataNavigatorInterface
{
    /**
     * @param mixed $data
     * @param VisitorInterface $visitor
     * @param array|null $config format of array is ['type' => ]
     *
     * @return mixed
     */
    public function navigate($data, VisitorInterface $visitor, array $config = null);

    /**
     * @param $className
     *
     * @return \Metadata\ClassHierarchyMetadata|\Metadata\MergeableClassMetadata
     *
     * @throws NoMetadataException
     */
    public function getMetadataForClass($className);
}
