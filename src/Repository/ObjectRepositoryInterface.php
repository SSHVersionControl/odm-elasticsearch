<?php

declare(strict_types=1);

namespace CCT\Component\ORMElasticsearch\Repository;

use CCT\Component\ORMElasticsearch\Repository\Model\DocumentSupportInterface;
use Elastica\Query\AbstractQuery;

/**
 * Interface ObjectRepositoryInterface. Based on doctrines object repository interface
 *
 * @package CCT\Component\ORMElasticsearch\Repository
 */
interface ObjectRepositoryInterface
{
    /**
     * Finds an object by its primary key / identifier.
     *
     * @param mixed $id The identifier.
     *
     * @return DocumentSupportInterface|null The object.
     */
    public function find($id): ?DocumentSupportInterface;

    /**
     * Finds all objects in the repository.
     *
     * @return DocumentSupportInterface[] The objects.
     */
    public function findAll(): array;

    /**
     * Finds objects by an elastica query.
     *
     * Optionally sorting and limiting details can be passed. An implementation may throw
     * an UnexpectedValueException if certain values of the sorting or limiting details are
     * not supported.
     *
     * @param AbstractQuery $query
     * @param string[]|null $orderBy
     * @param int|null $limit
     * @param int|null $offset
     *
     * @return DocumentSupportInterface[] The objects.
     *
     * @throws \UnexpectedValueException
     */
    public function findBy(AbstractQuery $query, ?array $orderBy = null, int $limit = null, int $offset = null): array;

    /**
     * Finds a single object by an elastica query
     *
     * @param AbstractQuery $query Elastica Query.
     *
     * @return DocumentSupportInterface|null The object.
     */
    public function findOneBy(AbstractQuery $query): ?DocumentSupportInterface;

    /**
     * Returns the class name of the object managed by the repository.
     *
     * @return string
     */
    public function getClassName(): string;
}
