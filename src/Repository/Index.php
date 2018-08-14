<?php

declare(strict_types=1);

namespace CCT\Component\ODMElasticsearch\Repository;

use Elastica\Client;
use Elastica\Document;
use Elastica\Exception\NotFoundException;
use Elastica\Index as BaseIndex;
use Elastica\Type;

/**
 * Use Type methods in index class as there is only one type allowed
 *
 * @see     \Elastica\Type          to have the access to the methods
 *
 * @method  \Elastica\Response               addDocument(Document $doc)
 * @method  \Elastica\Response               addObject($object, Document $doc = null)
 * @method  \Elastica\Response               updateDocument($data, array $options = [])
 * @method  \Elastica\Response               updateDocuments(array $docs, array $options = [])
 * @method  \Elastica\Bulk\ResponseSet       addObjects(array $objects, array $options = [])
 * @method  string                           getName()
 * @method  Document                         getDocument($id, $options = [])
 * @method  Document                         createDocument($id = '', $data = [])
 * @method  \Elastica\Response               setMapping($mapping, array $query = [])
 * @method  \Elastica\Response               getMapping()
 * @method  \Elastica\Response               deleteDocument(Document $document)
 * @method  \Elastica\Bulk\ResponseSet       deleteDocuments(array $docs)
 * @method  \Elastica\Response               deleteById($id, array $options = [])
 * @method  \Elastica\Response               deleteIds(array $ids, $routing = false)
 * @method  \Elastica\Response               deleteByQuery($query, array $options = [])
 */
class Index extends BaseIndex
{
    /**
     * @var Type
     */
    protected $type;

    /**
     * Index constructor.
     *
     * @param Client $client
     * @param string $name
     * @param string $typeName
     */
    public function __construct(Client $client, string $name, $typeName = 'record')
    {
        parent::__construct($client, $name);
        $this->type = new Type($this, $typeName);
    }

    /**
     * Elasticsearch type. To support removal of type in future versions of elastic search,
     * we use one one type per index with default name
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/master/removal-of-types.html
     *
     * @param string $type
     *
     * @return Type
     */
    public function getType($type = null): Type
    {
        return $this->type;
    }

    /**
     * Check if document exists in the index
     * @TODO use a HEAD request as we do not need the content
     *
     * @param $id
     * @param array $options
     *
     * @return bool
     */
    public function hasDocument($id, array $options = []): bool
    {
        try {
            $this->type->getDocument($id, $options);
        } catch (NotFoundException $exception) {
            return false;
        }

        return true;
    }

    /**
     * Uses _bulk to send documents to the server.
     *
     * @param array|\Elastica\Document[] $docs    Array of Elastica\Document
     * @param array $options Array of query params to use for query. For possible options check es api
     *
     * @return \Elastica\Bulk\ResponseSet
     */
    public function addDocuments(array $docs, array $options = [])
    {
        foreach ($docs as $doc) {
            $doc->setType($this->type->getName());
        }

        return parent::addDocuments($docs, $options);
    }


    /**
     * @param $name
     * @param $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (method_exists($this, $name)) {
            return \call_user_func_array(array($this, $name), $arguments);
        }

        if (method_exists($this->type, $name)) {
            return \call_user_func_array(array($this->type, $name), $arguments);
        }

        throw new \RuntimeException('The called method does not exist.');
    }
}
