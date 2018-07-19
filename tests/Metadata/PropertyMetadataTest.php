<?php

declare(strict_types=1);

namespace CCT\Component\ORMElasticsearch\Tests\Metadata;

use CCT\Component\ORMElasticsearch\Metadata\PropertyMetadata;
use CCT\Component\ORMElasticsearch\Tests\Fixture\FakeObject;
use PHPUnit\Framework\TestCase;

class PropertyMetadataTest extends TestCase
{

    public function testGetDefaultAccessorsWithPublic()
    {
        $object = new FakeObject();
        $object->name = 'Bob';

        $metadata = new PropertyMetadata(get_class($object), 'name');
        $metadata->setDefaultGetterAccessor();

        self::assertEquals('Bob', $metadata->getValue($object));
    }

    public function testGetDefaultAccessorsWithProtected()
    {
        $object = new FakeObject();
        $object->setWifeName('Sarah');

        $metadata = new PropertyMetadata(get_class($object), 'wifeName');
        $metadata->setDefaultGetterAccessor();

        self::assertEquals('Sarah', $metadata->getValue($object));
    }

    public function testGetDefaultAccessorsWithPrivate()
    {
        $object = new FakeObject();
        $object->setMistressName('Aoife');

        $metadata = new PropertyMetadata(get_class($object), 'mistressName');
        $metadata->setDefaultGetterAccessor();

        self::assertEquals('Aoife', $metadata->getValue($object));
    }

    public function testGetDefaultAccessorsWithIsFunction()
    {
        $object = new FakeObject();
        $object->setCaught(true);

        $metadata = new PropertyMetadata(get_class($object), 'caught');
        $metadata->setDefaultGetterAccessor();

        $this->assertTrue($metadata->getValue($object));
    }

    public function testSetDefaultAccessorsWithPublic()
    {
        $object = new FakeObject();

        $metadata = new PropertyMetadata(get_class($object), 'name');
        $metadata->setDefaultSetterAccessor();

        $metadata->setValue($object,'Bob');

        self::assertEquals('Bob', $object->name);
    }

    public function testSetDefaultAccessorsWithProtected()
    {
        $object = new FakeObject();

        $metadata = new PropertyMetadata(get_class($object), 'wifeName');
        $metadata->setDefaultSetterAccessor();

        $metadata->setValue($object,'Sarah');

        self::assertEquals('Sarah', $object->getWifeName());
    }

    public function testSetDefaultAccessorsWithPrivate()
    {
        $object = new FakeObject();

        $metadata = new PropertyMetadata(get_class($object), 'mistressName');
        $metadata->setDefaultSetterAccessor();

        $metadata->setValue($object,'Aoife');

        self::assertEquals('Aoife', $object->getMistressName());
    }
}

