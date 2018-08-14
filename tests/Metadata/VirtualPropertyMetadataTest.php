<?php

declare(strict_types=1);

namespace CCT\Component\ODMElasticsearch\Tests\Metadata;

use CCT\Component\ODMElasticsearch\Metadata\Exception\InvalidArgumentException;
use CCT\Component\ODMElasticsearch\Metadata\VirtualPropertyMetadata;
use CCT\Component\ODMElasticsearch\Tests\Fixture\FakeObject;
use PHPUnit\Framework\TestCase;

class VirtualPropertyMetadataTest extends TestCase
{
    public function testMethodNameWithGet(): void
    {
        $object = new FakeObject();

        $virtualProperty = new VirtualPropertyMetadata(FakeObject::class, 'getUnknownChildren');

        $this->assertEquals(20, $virtualProperty->getValue($object));
    }

    public function testMethodNameWithOutGet(): void
    {
        $object = new FakeObject();

        $virtualProperty = new VirtualPropertyMetadata(FakeObject::class, 'divorceFee');

        $this->assertEquals(100, $virtualProperty->getValue($object));
    }

    public function testSetValueThrowsException(): void
    {
        $this->expectException(\LogicException::class);
        $object = new FakeObject();
        $virtualProperty = new VirtualPropertyMetadata(FakeObject::class, 'divorceFee');

        $virtualProperty->setValue($object, 90);
    }

    public function testSetPublicPropertyThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $object = new FakeObject();
        $object->name = 'Tom';
        $virtualProperty = new VirtualPropertyMetadata(FakeObject::class, 'name');

        $virtualProperty->getValue($object);
    }
}
