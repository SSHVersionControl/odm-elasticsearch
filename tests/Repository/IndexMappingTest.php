<?php

declare(strict_types=1);

namespace CCT\Component\ORMElasticsearch\Tests\Repository;

use CCT\Component\ORMElasticsearch\Metadata\Driver\YamlDriver;
use CCT\Component\ORMElasticsearch\Repository\Exception\NoMetadataConfigException;
use CCT\Component\ORMElasticsearch\Repository\Index;
use CCT\Component\ORMElasticsearch\Repository\IndexMapping;
use CCT\Component\ORMElasticsearch\Tests\Fixture\FakeObject;
use Elastica\Client;
use Elastica\Response;
use Metadata\Driver\FileLocator;
use Metadata\MetadataFactory;
use PHPUnit\Framework\TestCase;

class IndexMappingTest extends TestCase
{
    public function testGetIndexForEntityClass(): void
    {
        $indexMapping = $this->createIndexMapping();

        $index = $indexMapping->getIndex(FakeObject::class);

        $this->assertInstanceOf(Index::class, $index);
    }

    public function testGetIndexThrowsNoMetadataConfigException(): void
    {
        $this->expectException(NoMetadataConfigException::class);

        $indexMapping = $this->createIndexMapping();

        $indexMapping->getIndex(__CLASS__);
    }

    public function testGetIndexWithoutIndexConfigThrowsNoMetadataConfigException(): void
    {
        $this->expectException(NoMetadataConfigException::class);

        $client = $this->createMockClient();

        $response = new Response('', 200);
        $client->method('requestEndpoint')->willReturn($response);

        $metadataFactory = $this->createMetadataFactory('/../Fixture/config/noIndex');

        $indexMapping = new IndexMapping($client, $metadataFactory);

        $indexMapping->getIndex(FakeObject::class);
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
}
