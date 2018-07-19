<?php

declare(strict_types=1);

namespace CCT\Component\ORMElasticsearch\Tests\Repository;

use CCT\Component\ORMElasticsearch\Repository\Index;
use Elastica\Client;
use Elastica\Type;
use PHPUnit\Framework\TestCase;

class IndexTest extends TestCase
{
    public function testConstructShouldCreateType(): void
    {
        $client = $this->createMock(Client::class);
        $index = new Index($client, 'TestIndex');
        $type = $index->getType();
        $this->assertInstanceOf(Type::class, $type);
        $this->assertEquals('record', $type->getName());
    }

    public function testConstructWithTypeNameShouldCreateType(): void
    {
        $client = $this->createMock(Client::class);
        $index = new Index($client, 'TestIndex', 'dummy');
        $type = $index->getType();
        $this->assertInstanceOf(Type::class, $type);
        $this->assertEquals('dummy', $type->getName());
    }
}
