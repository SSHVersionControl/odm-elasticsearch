<?php
declare(strict_types=1);

namespace CCT\Component\ORMElasticsearch\Repository;

interface IndexMappingInterface
{
    /**
     * Get Elastica Index. If index does not exists, it creates one. If mapping is change then its updated
     *
     * @param string $entityName
     *
     * @return Index
     * @throws \Exception
     */
    public function getIndex(string $entityName): Index;
}
