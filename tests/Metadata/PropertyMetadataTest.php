<?php

declare(strict_types=1);

namespace CCT\Component\ODMElasticsearch\Tests\Metadata;

use CCT\Component\ODMElasticsearch\Metadata\PropertyMetadata;
use CCT\Component\ODMElasticsearch\Tests\Fixture\FakeObject;
use PHPUnit\Framework\TestCase;

class PropertyMetadataTest extends TestCase
{
    public function testGetDefaultAccessorsWithPublic(): void
    {
        $object = new FakeObject();
        $object->name = 'Bob';

        $metadata = new PropertyMetadata(\get_class($object), 'name');
        $metadata->setDefaultGetterAccessor();

        self::assertEquals('Bob', $metadata->getValue($object));
    }

    public function testGetDefaultAccessorsWithProtected(): void
    {
        $object = new FakeObject();
        $object->setWifeName('Sarah');

        $metadata = new PropertyMetadata(\get_class($object), 'wifeName');
        $metadata->setDefaultGetterAccessor();

        self::assertEquals('Sarah', $metadata->getValue($object));
    }

    public function testGetDefaultAccessorsWithPrivate(): void
    {
        $object = new FakeObject();
        $object->setMistressName('Aoife');

        $metadata = new PropertyMetadata(\get_class($object), 'mistressName');
        $metadata->setDefaultGetterAccessor();

        self::assertEquals('Aoife', $metadata->getValue($object));
    }

    public function testGetDefaultAccessorsWithIsFunction(): void
    {
        $object = new FakeObject();
        $object->setCaught(true);

        $metadata = new PropertyMetadata(\get_class($object), 'caught');
        $metadata->setDefaultGetterAccessor();

        $this->assertTrue($metadata->getValue($object));
    }

    public function testSetDefaultAccessorsWithPublic(): void
    {
        $object = new FakeObject();

        $metadata = new PropertyMetadata(\get_class($object), 'name');
        $metadata->setDefaultSetterAccessor();

        $metadata->setValue($object, 'Bob');

        self::assertEquals('Bob', $object->name);
    }

    public function testSetDefaultAccessorsWithProtected(): void
    {
        $object = new FakeObject();

        $metadata = new PropertyMetadata(\get_class($object), 'wifeName');
        $metadata->setDefaultSetterAccessor();

        $metadata->setValue($object, 'Sarah');

        self::assertEquals('Sarah', $object->getWifeName());
    }

    public function testSetDefaultAccessorsWithPrivate(): void
    {
        $object = new FakeObject();

        $metadata = new PropertyMetadata(\get_class($object), 'mistressName');
        $metadata->setDefaultSetterAccessor();

        $metadata->setValue($object, 'Aoife');

        self::assertEquals('Aoife', $object->getMistressName());
    }
}
