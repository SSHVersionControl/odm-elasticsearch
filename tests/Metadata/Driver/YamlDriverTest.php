<?php

declare(strict_types=1);

namespace CCT\Component\ODMElasticsearch\Tests\Metadata\Driver;

use CCT\Component\ODMElasticsearch\Metadata\ClassMetadata;
use CCT\Component\ODMElasticsearch\Metadata\Driver\YamlDriver;
use CCT\Component\ODMElasticsearch\Metadata\PropertyMetadata;
use CCT\Component\ODMElasticsearch\Tests\Fixture\FakeObject;
use Metadata\Driver\FileLocator;
use PHPUnit\Framework\TestCase;
use CCT\Component\ODMElasticsearch\Tests\Fixture\FakeObjectRepository;

class YamlDriverTest extends TestCase
{
    /**
     * @var FileLocator
     */
    protected $fileLocator;

    public function setUp()
    {
        $this->fileLocator = new FileLocator(
            ['CCT\Component\ODMElasticsearch\Tests\Fixture' => __DIR__ . '/../../Fixture/config']
        );

        parent::setUp();
    }

    public function testLoadMetadataFromFile(): void
    {
        $yamlDriver = new YamlDriver($this->fileLocator);
        $class = new \ReflectionClass(FakeObject::class);
        $metadata = $yamlDriver->loadMetadataForClass($class);

        $this->assertInstanceOf(ClassMetadata::class, $metadata);
    }

    public function testParseConfig(): void
    {
        $yamlDriver = new YamlDriver($this->fileLocator);
        $class = new \ReflectionClass(FakeObject::class);

        /** @var ClassMetadata $metadata */
        $metadata = $yamlDriver->loadMetadataForClass($class);

        $this->assertInternalType('array', $metadata->getIndex());
        $this->assertEquals(FakeObjectRepository::class, $metadata->getCustomRepositoryName());

        $this->assertInternalType('array', $metadata->propertyMetadata);
        $propertyMetadata = reset($metadata->propertyMetadata);

        $this->assertInstanceOf(PropertyMetadata::class, $propertyMetadata);
    }

    public function testExposeAllFalse(): void
    {
        $yamlDriver = new YamlDriver($this->fileLocator);
        $class = new \ReflectionClass(FakeObject::class);

        /** @var ClassMetadata $metadata */
        $metadata = $yamlDriver->loadMetadataForClass($class);

        $this->assertCount(1, $metadata->propertyMetadata);
    }

    public function testExposeAllTrue(): void
    {
        $fileLocator = new FileLocator(
            ['CCT\Component\ODMElasticsearch\Tests\Fixture' => __DIR__ . '/../../Fixture/config/exposeAll']
        );

        $yamlDriver = new YamlDriver($fileLocator);
        $class = new \ReflectionClass(FakeObject::class);

        /** @var ClassMetadata $metadata */
        $metadata = $yamlDriver->loadMetadataForClass($class);

        $this->assertCount(4, $metadata->propertyMetadata);
    }

    public function testHidePropertyWithExposeAllTrue(): void
    {
        $fileLocator = new FileLocator(
            ['CCT\Component\ODMElasticsearch\Tests\Fixture' => __DIR__ . '/../../Fixture/config/hideProperty']
        );

        $yamlDriver = new YamlDriver($fileLocator);
        $class = new \ReflectionClass(FakeObject::class);

        /** @var ClassMetadata $metadata */
        $metadata = $yamlDriver->loadMetadataForClass($class);

        $this->assertCount(3, $metadata->propertyMetadata);
    }
}
