<?php
declare(strict_types=1);

namespace CCT\Component\ORMElasticsearch\Transformer;

use CCT\Component\ORMElasticsearch\Transformer\Exception\NoMetadataException;
use CCT\Component\ORMElasticsearch\Transformer\Visitor\VisitorInterface;

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
