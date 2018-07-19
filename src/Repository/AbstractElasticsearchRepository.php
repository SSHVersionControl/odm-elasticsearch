<?php

declare(strict_types=1);

namespace CCT\Component\ORMElasticsearch\Repository;

use CCT\Component\ORMElasticsearch\Repository\Model\DocumentSupportInterface;
use CCT\Component\ORMElasticsearch\Repository\Traits\AggregationTrait;
use CCT\Component\ORMElasticsearch\Transformer\DataTransformerInterface;
use Elastica\Document;
use Elastica\Exception\InvalidException;
use Elastica\Index;
use Elastica\Query;
use Elastica\Query\BoolQuery;
use Elastica\Query\Match;

abstract class AbstractElasticsearchRepository implements ObjectRepositoryInterface
{
    use AggregationTrait;

    /**
     * The entity class name
     *
     * @var string
     */
    protected $entityName;

    /**
     * @var Index
     */
    protected $index;

    /**
     * @var Query
     */
    protected $query;

    /**
     * @var DataTransformerInterface
     */
    protected $dataTransformer;

    /**
     * @var array
     */
    protected $errors;

    /**
     * AbstractElasticsearchRepository constructor.
     * Creates the index on Elasticsearch if not existing already.
     *
     * @param string $entityName
     * @param IndexMapping $indexMapping
     * @param DataTransformerInterface $dataTransformer
     *
     * @throws \Exception
     */
    public function __construct(
        string $entityName,
        IndexMapping $indexMapping,
        DataTransformerInterface $dataTransformer
    ) {
        $this->entityName = $entityName;

        $this->index = $indexMapping->getIndex($entityName);

        $this->dataTransformer = $dataTransformer;

        $this->query = new Query();
        $this->errors = [];
    }

    /**
     * Elastic search index name
     *
     * @return string
     */
    abstract public function getIndexName(): string;

    /**
     *
     */
    public function getResults(): void
    {
        $resultSet = $this->index->search($this->query);
    }

    public function getScalarResults(): array
    {
        $resultSet = $this->index->search($this->query);

        return $resultSet->getResponse()->getData();
    }

    /**
     * Get count of query
     *
     * @return int number of documents matching the query
     */
    public function getCount(): int
    {
        return $this->index->count($this->query);
    }

    /**
     * @param DocumentSupportInterface $object
     *
     * @return bool
     */
    public function save(DocumentSupportInterface $object): bool
    {
        if ($this->index->hasDocument($object->getId())) {
            return $this->update($object);
        }

        return $this->insert($object);
    }

    /**
     * Bulk insert of multiple objects
     *
     * @param array $objects
     *
     * @return bool
     */
    public function batchInsert(array $objects): bool
    {
        $documents = [];
        foreach ($objects as $object) {
            $documents[] = $this->transformToDocument($object);
        }

        $this->index->addDocuments($documents);
        $this->index->refresh();

        return true;
    }

    /**
     * Adds a limit of results to the query.
     *
     * @param int $limit
     *
     * @return void
     */
    public function limit($limit): void
    {
        $this->query->setSize($limit);
    }

    /**
     * Just check if the there is some result for the criteria.
     * The result is based on ID column.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return $this->index->exists();
    }

    /**
     * Clone the criteria object, returning its duplicated.
     *
     * @return static
     */
    public function duplicate()
    {
        return clone $this;
    }

    /**
     * Specifies an ordering for the query results.
     * Replaces any previously specified orderings, if any.
     *
     * @param string $sort The ordering expression.
     * @param string $order The ordering direction.
     *
     * @return void
     */
    public function orderBy($sort, $order = null): void
    {
        $direction = $order ?? 'asc';
        $this->query->setSort([$sort => $direction]);
    }

    /**
     * It clears the current Criteria state (remove the filters).
     *
     * @return void
     */
    public function clear(): void
    {
        $this->query = new Query();
    }

    /**
     * Find entity by id on elastic search
     *
     * @param $id
     *
     * @return DocumentSupportInterface|null
     */
    public function findById($id): ?DocumentSupportInterface
    {
        $document = $this->index->getDocument($id);

        return $this->reverseTransform($document);
    }

    /**
     * @param DocumentSupportInterface $object
     *
     * @return bool
     */
    public function insert(DocumentSupportInterface $object): bool
    {
        $document = $this->transformToDocument($object);

        try {
            $this->index->addDocument($document);
        } catch (InvalidException $exception) {
            $this->log($exception->getMessage());

            return false;
        }

        return true;
    }

    /**
     * @param DocumentSupportInterface $object
     *
     * @return bool
     */
    public function update(DocumentSupportInterface $object): bool
    {
        $document = $this->transformToDocument($object);

        try {
            $this->index->updateDocument($document);
        } catch (InvalidException $exception) {
            $this->log($exception->getMessage());

            return false;
        }

        return true;
    }

    /**
     * @param DocumentSupportInterface $object
     *
     * @return bool
     */
    public function delete(DocumentSupportInterface $object): bool
    {
        $document = $this->transformToDocument($object);

        try {
            $this->index->deleteDocument($document);
        } catch (InvalidException $exception) {
            $this->log($exception->getMessage());

            return false;
        }

        return true;
    }

    /**
     * Creates an elastica document
     *
     * @param DocumentSupportInterface $object
     *
     * @return Document
     */
    public function transformToDocument(DocumentSupportInterface $object): Document
    {
        if (null === $object->getId()) {
            throw new \RuntimeException('Object must have and id function getId()');
        }

        $data = $this->dataTransformer->transform($object);

        if (null === $data) {
            throw new \RuntimeException(
                'Data was not converted to array. Please check a config for ' . \get_class($object)
            );
        }

        return new Document($object->getId(), $data);
    }

    /**
     * Convert elastica document to entity object
     *
     * @param Document $document
     *
     * @return DocumentSupportInterface
     */
    public function reverseTransform(Document $document): DocumentSupportInterface
    {
        $object = new $this->entityName;
        $documentData = $document->getData();
        if (!\is_array($documentData)) {
            return null;
        }
        $this->dataTransformer->reverseTransform($document->getData(), $object);

        return $object;
    }

    /**
     * Get any errors logged
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Log error and other messages
     *
     * @param string $message
     * @param string $type
     */
    public function log(string $message, string $type = 'error'): void
    {
        if ('error' === $type) {
            $this->errors[] = $message;
        }
    }

    /**
     * @param $fieldName
     * @param $value
     * @param string $operator
     */
    public function addFilter($fieldName, $value, $operator = Match::OPERATOR_AND): void
    {
        try {
            $boolQuery = $this->query->getQuery();
        } catch (InvalidException $exception) {
            $boolQuery = new BoolQuery();
            $this->query->setQuery($boolQuery);
        }

        $filter = new Match();
        $filter->setFieldQuery($fieldName, $value);
        $filter->setFieldOperator($fieldName, $operator);

        $boolQuery->addFilter($filter);
    }

    /**
     * Finds an object by its primary key / identifier.
     *
     * @param mixed $id The identifier.
     *
     * @return object|null The object.
     */
    public function find($id)
    {
        return $this->findById($id);
    }

    /**
     * Finds all objects in the repository.
     *
     * @return object[] The objects.
     */
    public function findAll()
    {
        $resultSet = $this->index->search($this->query);

        $objects = [];

        foreach ($resultSet->getDocuments() as $document) {
            $objects = $this->reverseTransform($document);
        }

        return $objects;
    }

    /**
     * Finds objects by a set of criteria.
     *
     * Optionally sorting and limiting details can be passed. An implementation may throw
     * an UnexpectedValueException if certain values of the sorting or limiting details are
     * not supported.
     *
     * @param mixed[] $criteria
     * @param string[]|null $orderBy
     * @param int|null $limit
     * @param int|null $offset
     *
     * @return object[] The objects.
     *
     * @throws \UnexpectedValueException
     */
    public function findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
    {
        //$this->query->
        $resultSet = $this->index->search($this->query);

        $objects = [];

        foreach ($resultSet->getDocuments() as $document) {
            $objects = $this->reverseTransform($document);
        }

        return $objects;
    }

    /**
     * Finds a single object by a set of criteria.
     *
     * @param mixed[] $criteria The criteria.
     *
     * @return object|null The object.
     */
    public function findOneBy(array $criteria)
    {
        $resultSet = $this->index->search($this->query);
        $document = current($resultSet->getDocuments());
        if (false === $document) {
            return null;
        }

        return $this->reverseTransform($document);
    }

    /**
     * Returns the class name of the object managed by the repository.
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->entityName;
    }
}
