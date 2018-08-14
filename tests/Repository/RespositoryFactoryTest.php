<?php

declare(strict_types=1);

namespace CCT\Component\ODMElasticsearch\Tests\Repository;

use CCT\Component\ODMElasticsearch\Metadata\Driver\YamlDriver;
use CCT\Component\ODMElasticsearch\Repository\ElasticsearchRepository;
use CCT\Component\ODMElasticsearch\Repository\Exception\InvalidArgumentException;
use CCT\Component\ODMElasticsearch\Repository\Exception\NoMetadataConfigException;
use CCT\Component\ODMElasticsearch\Repository\IndexMapping;
use CCT\Component\ODMElasticsearch\Repository\RepositoryFactory;
use CCT\Component\ODMElasticsearch\Tests\Fixture\FakeObject;
use CCT\Component\ODMElasticsearch\Tests\Fixture\FakeObjectNoDocumentInterface;
use CCT\Component\ODMElasticsearch\Tests\Fixture\FakeObjectRepository;
use CCT\Component\ODMElasticsearch\Tests\Fixture\FakeObjectWithRelatedObject;
use CCT\Component\ODMElasticsearch\Transformer\DataTransformerInterface;
use Elastica\Client;
use Elastica\Response;
use Metadata\Driver\FileLocator;
use Metadata\MetadataFactory;
use PHPUnit\Framework\TestCase;

class RespositoryFactoryTest extends TestCase
{

    public function testGetRepositoryShouldReturnDefaultRepository(): void
    {
        $metadataFactory = $this->createMetadataFactory();
        $dataTransformer = $this->createMock(DataTransformerInterface::class);
        $indexMapping = $this->createIndexMapping();
        $repositoryFactory = new RepositoryFactory($indexMapping, $dataTransformer, $metadataFactory);

        $repository = $repositoryFactory->getRepository(FakeObjectWithRelatedObject::class);
        $this->assertInstanceOf(ElasticsearchRepository::class, $repository);
    }

    public function testGetRepositoryShouldReturnCustomRepository(): void
    {
        $metadataFactory = $this->createMetadataFactory();
        $dataTransformer = $this->createMock(DataTransformerInterface::class);
        $indexMapping = $this->createIndexMapping();
        $repositoryFactory = new RepositoryFactory($indexMapping, $dataTransformer, $metadataFactory);

        $repository = $repositoryFactory->getRepository(FakeObject::class);
        $this->assertInstanceOf(FakeObjectRepository::class, $repository);
    }

    public function testGetRepositoryThrowNoMetadataConfigException(): void
    {
        $this->expectException(NoMetadataConfigException::class);

        $metadataFactory = $this->createMetadataFactory('/../Fixture/');
        $dataTransformer = $this->createMock(DataTransformerInterface::class);
        $indexMapping = $this->createIndexMapping();
        $repositoryFactory = new RepositoryFactory($indexMapping, $dataTransformer, $metadataFactory);

        $repositoryFactory->getRepository(FakeObject::class);
    }

    public function testGetRepositoryThrowInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $metadataFactory = $this->createMetadataFactory();
        $dataTransformer = $this->createMock(DataTransformerInterface::class);
        $indexMapping = $this->createIndexMapping();
        $repositoryFactory = new RepositoryFactory($indexMapping, $dataTransformer, $metadataFactory);

        $repositoryFactory->getRepository(FakeObjectNoDocumentInterface::class);
    }

    protected function createMetadataFactory($configDir = '/../Fixture/config')
    {
        $fileLocator = new FileLocator(
            ['CCT\Component\ODMElasticsearch\Tests\Fixture' => __DIR__ . $configDir]
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

        $indexMapping = $this->getMockBuilder(IndexMapping::class)
            ->setConstructorArgs([$client, $metadataFactory])
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->setMethods(['getMappingDifference'])
            ->getMock();

        $indexMapping->method('getMappingDifference')->willReturn([]);

        return $indexMapping;
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
