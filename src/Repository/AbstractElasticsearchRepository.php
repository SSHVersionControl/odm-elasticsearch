<?php

declare(strict_types=1);

namespace CCT\Component\ODMElasticsearch\Repository;

use CCT\Component\ODMElasticsearch\Repository\Exception\ReverseTransformationException;
use CCT\Component\ODMElasticsearch\Repository\Model\DocumentSupportInterface;
use CCT\Component\ODMElasticsearch\Repository\Traits\AggregationTrait;
use CCT\Component\ODMElasticsearch\Transformer\DataTransformerInterface;
use Elastica\Document;
use Elastica\Exception\InvalidException;
use Elastica\Index;
use Elastica\Query;
use Elastica\Query\AbstractQuery;

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
     * Gets the results as hydrated objects
     *
     * @return array
     * @throws \ReflectionException
     */
    public function getResults(): array
    {
        $resultSet = $this->index->search($this->query);

        $objects = [];

        foreach ($resultSet->getDocuments() as $document) {
            $objects[] = $this->reverseTransform($document);
        }

        return $objects;
    }

    /**
     * Gets the scalar results for the query.
     *
     * @return array
     */
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
     * Offset\from of results to the query.
     *
     * @param int $offset
     *
     * @return void
     */
    public function offset($offset): void
    {
        $this->query->setFrom($offset);
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
     * @throws \ReflectionException
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
     * @throws \ReflectionException
     */
    public function reverseTransform(Document $document): DocumentSupportInterface
    {
        $entity = new \ReflectionClass($this->entityName);
        /** @var DocumentSupportInterface $object */
        $object = $entity->newInstanceWithoutConstructor();

        if (false === $object instanceof DocumentSupportInterface) {
            throw new ReverseTransformationException(
                sprintf('Entity "%s" is not an instance of "%s"', $this->entityName, DocumentSupportInterface::class)
            );
        }

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
     * {@inheritdoc}
     * @throws \ReflectionException
     */
    public function find($id): ?DocumentSupportInterface
    {
        return $this->findById($id);
    }

    /**
     * {@inheritdoc}
     */
    public function findAll(): array
    {
        $this->clear();

        $maxSize = $this->getCount();

        $this->query->setSize($maxSize);

        $this->query->setFrom(0);

        return $this->getResults();
    }

    /**
     * {@inheritdoc}
     */
    public function findBy(AbstractQuery $query, ?array $orderBy = null, int $limit = null, int $offset = null): array
    {
        $this->clear();

        $this->query->setQuery($query);

        if (null !== $orderBy && \count($orderBy) > 0) {
            $sort = reset($orderBy);
            $direction = next($orderBy) === 'desc' ? 'desc' : 'asc';
            $this->orderBy($sort, $direction);
        }

        $this->query->setSize($limit ?? 1000);

        $this->query->setFrom($offset ?? 0);

        return $this->getResults();
    }

    /**
     * {@inheritdoc}
     */
    public function findOneBy(AbstractQuery $query): ?DocumentSupportInterface
    {
        $this->clear();

        $this->query->setQuery($query);

        $resultSet = $this->index->search($this->query);

        $document = current($resultSet->getDocuments());
        if (false === $document) {
            return null;
        }

        return $this->reverseTransform($document);
    }

    /**
     * {@inheritdoc}
     */
    public function getClassName(): string
    {
        return $this->entityName;
    }
}
