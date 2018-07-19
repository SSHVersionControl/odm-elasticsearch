<?php

declare(strict_types=1);

namespace CCT\Component\ORMElasticsearch\Tests\Repository;

use CCT\Component\ORMElasticsearch\Metadata\Driver\YamlDriver;
use CCT\Component\ORMElasticsearch\Repository\ElasticsearchRepository;
use CCT\Component\ORMElasticsearch\Repository\Exception\InvalidArgumentException;
use CCT\Component\ORMElasticsearch\Repository\Exception\NoMetadataConfigException;
use CCT\Component\ORMElasticsearch\Repository\IndexMapping;
use CCT\Component\ORMElasticsearch\Repository\RepositoryFactory;
use CCT\Component\ORMElasticsearch\Tests\Fixture\FakeObject;
use CCT\Component\ORMElasticsearch\Tests\Fixture\FakeObjectNoDocumentInterface;
use CCT\Component\ORMElasticsearch\Tests\Fixture\FakeObjectRepository;
use CCT\Component\ORMElasticsearch\Tests\Fixture\FakeObjectWithRelatedObject;
use CCT\Component\ORMElasticsearch\Transformer\DataTransformerInterface;
use Elastica\Client;
use Elastica\Response;
use Metadata\Driver\FileLocator;
use Metadata\MetadataFactory;
use PHPUnit\Framework\TestCase;

class RespositoryFactoryTest extends TestCase
{

    public function testGetRepositoryShouldReturnDefaultRepository()
    {
        $metadataFactory = $this->createMetadataFactory();
        $dataTransformer = $this->createMock(DataTransformerInterface::class);
        $indexMapping = $this->createIndexMapping();
        $repositoryFactory = new RepositoryFactory($indexMapping, $dataTransformer, $metadataFactory);

        $repository = $repositoryFactory->getRepository(FakeObjectWithRelatedObject::class);
        $this->assertInstanceOf(ElasticsearchRepository::class, $repository);
    }

    public function testGetRepositoryShouldReturnCustomRepository()
    {
        $metadataFactory = $this->createMetadataFactory();
        $dataTransformer = $this->createMock(DataTransformerInterface::class);
        $indexMapping = $this->createIndexMapping();
        $repositoryFactory = new RepositoryFactory($indexMapping, $dataTransformer, $metadataFactory);

        $repository = $repositoryFactory->getRepository(FakeObject::class);
        $this->assertInstanceOf(FakeObjectRepository::class, $repository);
    }

    public function testGetRepositoryThrowNoMetadataConfigException()
    {
        $this->expectException(NoMetadataConfigException::class);

        $metadataFactory = $this->createMetadataFactory('/../Fixture/');
        $dataTransformer = $this->createMock(DataTransformerInterface::class);
        $indexMapping = $this->createIndexMapping();
        $repositoryFactory = new RepositoryFactory($indexMapping, $dataTransformer, $metadataFactory);

        $repository = $repositoryFactory->getRepository(FakeObject::class);
    }

    public function testGetRepositoryThrowInvalidArgumentException()
    {
        $this->expectException(InvalidArgumentException::class);

        $metadataFactory = $this->createMetadataFactory();
        $dataTransformer = $this->createMock(DataTransformerInterface::class);
        $indexMapping = $this->createIndexMapping();
        $repositoryFactory = new RepositoryFactory($indexMapping, $dataTransformer, $metadataFactory);

        $repository = $repositoryFactory->getRepository(FakeObjectNoDocumentInterface::class);
    }

    protected function createMetadataFactory($configDir = '/../Fixture/config')
    {
        $fileLocator = new FileLocator(
            ['CCT\Component\ORMElasticsearch\Tests\Fixture' => __DIR__ . $configDir]
        );

        $yamlDriver = new YamlDriver($fileLocator);

        return new MetadataFactory($yamlDriver);
    }

    protected function createIndexMapping(): IndexMapping
    {
        $client = $this->createMockClient();

        $response = new Response('', 200);
        $client->method('requestEndpoint')->willReturn($response);

        $metadataFactory = $this->createMetadataFactory();

        return new IndexMapping($client, $metadataFactory);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject| Client
     */
    protected function createMockClient()
    {
        $client = $this->createPartialMock(
            Client::class,
            ['requestEndpoint']
        );

        return $client;
    }
}
