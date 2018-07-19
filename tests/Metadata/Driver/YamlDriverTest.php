<?php

declare(strict_types=1);

namespace CCT\Component\ORMElasticsearch\Tests\Metadata\Driver;

use CCT\Component\ORMElasticsearch\Metadata\ClassMetadata;
use CCT\Component\ORMElasticsearch\Metadata\Driver\YamlDriver;
use CCT\Component\ORMElasticsearch\Metadata\PropertyMetadata;
use CCT\Component\ORMElasticsearch\Tests\Fixture\FakeObject;
use Metadata\Driver\FileLocator;
use PHPUnit\Framework\TestCase;

class YamlDriverTest extends TestCase
{
    /**
     * @var FileLocator
     */
    protected $fileLocator;

    public function setUp()
    {
        $this->fileLocator = new FileLocator(
            ['CCT\Component\ORMElasticsearch\Tests\Fixture' => __DIR__.'/../../Fixture/config']
        );

        parent::setUp();
    }

    public function testLoadMetadataFromFile()
    {
        $yamlDriver = new YamlDriver($this->fileLocator);
        $class = new \ReflectionClass(FakeObject::class);
        $metadata = $yamlDriver->loadMetadataForClass($class);

        $this->assertInstanceOf(ClassMetadata::class,$metadata);
    }

    public function testParseConfig()
    {
        $yamlDriver = new YamlDriver($this->fileLocator);
        $class = new \ReflectionClass(FakeObject::class);

        /** @var ClassMetadata $metadata */
        $metadata = $yamlDriver->loadMetadataForClass($class);

        $this->assertTrue(is_array($metadata->getIndex()));
        $this->assertEquals('CCT\Component\ORMElasticsearch\Tests\Fixture\FakeObjectRepository', $metadata->getCustomRepositoryName());

        $this->assertTrue(is_array($metadata->propertyMetadata));
        $propertyMetadata = reset($metadata->propertyMetadata);

        $this->assertInstanceOf(PropertyMetadata::class, $propertyMetadata);
    }

    public function testExposeAllFalse()
    {
        $yamlDriver = new YamlDriver($this->fileLocator);
        $class = new \ReflectionClass(FakeObject::class);

        /** @var ClassMetadata $metadata */
        $metadata = $yamlDriver->loadMetadataForClass($class);

        $this->assertCount(1, $metadata->propertyMetadata);
    }

    public function testExposeAllTrue()
    {
        $fileLocator = new FileLocator(
            ['CCT\Component\ORMElasticsearch\Tests\Fixture' => __DIR__.'/../../Fixture/config/exposeAll']
        );

        $yamlDriver = new YamlDriver($fileLocator);
        $class = new \ReflectionClass(FakeObject::class);

        /** @var ClassMetadata $metadata */
        $metadata = $yamlDriver->loadMetadataForClass($class);

        $this->assertCount(4, $metadata->propertyMetadata);
    }

    public function testHidePropertyWithExposeAllTrue()
    {
        $fileLocator = new FileLocator(
            ['CCT\Component\ORMElasticsearch\Tests\Fixture' => __DIR__.'/../../Fixture/config/hideProperty']
        );

        $yamlDriver = new YamlDriver($fileLocator);
        $class = new \ReflectionClass(FakeObject::class);

        /** @var ClassMetadata $metadata */
        $metadata = $yamlDriver->loadMetadataForClass($class);

        $this->assertCount(3, $metadata->propertyMetadata);
    }
}
