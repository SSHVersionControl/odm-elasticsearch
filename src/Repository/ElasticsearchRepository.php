<?php

declare(strict_types=1);

namespace CCT\Component\ORMElasticsearch\Repository;

class ElasticsearchRepository extends AbstractElasticsearchRepository
{
    /**
     * Elastic search index name
     *
     * @return string
     */
    public function getIndexName(): string
    {
        return $this->getIndexNameFromEntityName();
    }

    /**
     * Converts entity name to index name by removing namespace and converting to snake case.
     *
     * @return string
     */
    protected function getIndexNameFromEntityName(): string
    {
        $className = substr($this->entityName, strrpos($this->entityName, '\\') + 1);

        return $this->camelCaseToSnakeCase($className);
    }

    /**
     * Converts string from camelCase to snake_case
     *
     * @param string $name
     *
     * @return string
     */
    protected function camelCaseToSnakeCase(string $name): string
    {
        return strtolower(
            ltrim(preg_replace('/[A-Z]([A-Z](?![a-z]))*/', '_$0', $name), '_')
        );
    }
}
