<?php

declare(strict_types=1);

namespace CCT\Component\ODMElasticsearch\Tests\Repository;

use CCT\Component\ODMElasticsearch\Metadata\Driver\YamlDriver;
use CCT\Component\ODMElasticsearch\Repository\Exception\NoMetadataConfigException;
use CCT\Component\ODMElasticsearch\Repository\Index;
use CCT\Component\ODMElasticsearch\Repository\IndexMapping;
use CCT\Component\ODMElasticsearch\Tests\Fixture\FakeObject;
use Elastica\Client;
use Elastica\Response;
use Metadata\Driver\FileLocator;
use Metadata\MetadataFactory;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

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
     * @dataProvider dataProviderForMappingDifference
     *
     * @param $expectedDifference
     * @param $mapping1
     * @param $mapping2
     *
     * @throws \ReflectionException
     */
    public function testMappingDifference($expectedDifference, $mapping1, $mapping2): void
    {
        $client = $this->createMockClient();

        $response = new Response('', 200);
        $client->method('requestEndpoint')->willReturn($response);

        $metadataFactory = $this->createMetadataFactory();

        $indexMapping = new IndexMapping($client, $metadataFactory);

        $class = new ReflectionClass(IndexMapping::class);
        $method = $class->getMethod('getDifferenceBetweenMultiArray');
        $method->setAccessible(true);

        $this->assertEquals($expectedDifference, $method->invoke($indexMapping, $mapping1, $mapping2));
    }

    public function dataProviderForMappingDifference()
    {
        return [
            [ [],['test'=>['hello']],['test'=>['hello']] ],
            [ ['not_here' => 'here_i_am'],['test'=>['hello'], 'not_here' => 'here_i_am'],['test'=>['hello']] ]

        ];
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

        return new IndexMapping($client, $metadataFactory);
    }
}
