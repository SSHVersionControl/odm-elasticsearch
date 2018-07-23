<?php

declare(strict_types=1);

namespace CCT\Component\ORMElasticsearch\Tests\Transformer;

use CCT\Component\ORMElasticsearch\Metadata\Driver\YamlDriver;
use CCT\Component\ORMElasticsearch\Tests\Fixture\FakeObject;
use CCT\Component\ORMElasticsearch\Tests\Fixture\FakeObjectWithRelatedObject;
use CCT\Component\ORMElasticsearch\Tests\Fixture\Visitor\FakeVisitor;
use CCT\Component\ORMElasticsearch\Transformer\DataNavigator;
use CCT\Component\ORMElasticsearch\Transformer\Visitor\AbstractVisitor;
use CCT\Component\ORMElasticsearch\Transformer\Visitor\ElasticsearchVisitor;
use CCT\Component\ORMElasticsearch\Transformer\Visitor\ReverseElasticsearchVisitor;
use CCT\Component\ORMElasticsearch\Transformer\Visitor\VisitorInterface;
use Metadata\Driver\FileLocator;
use Metadata\MetadataFactory;
use Metadata\MetadataFactoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DataNavigatorTest extends TestCase
{
    public function dataProviderForSuggestNavigate(): array
    {
        return [
            ['visitNull', null],
            ['visitString', 'test'],
            ['visitBoolean', false],
            ['visitBoolean', true],
            ['visitDouble', 45.657],
            ['visitInteger', 300],
            ['visitDateTime', new \DateTime()],
            ['visitTime', '23:45:01'],
            ['visitString', ' Here is a time 23:45:01'],
            ['visitArray', []],
        ];
    }

    /**
     * @param $functonToBeCalled
     * @param $value
     *
     * @dataProvider dataProviderForSuggestNavigate
     */
    public function testNavigateSuggest($functonToBeCalled, $value): void
    {
        $metadataFactory = $this->createMock(MetadataFactoryInterface::class);
        $dataNavigator = new DataNavigator($metadataFactory);
        $visitor = $this->createVisitorMock();

        $visitor->expects($this->once())
            ->method($functonToBeCalled);

        $dataNavigator->navigate($value, $visitor);
    }

    /**
     * @return MockObject|VisitorInterface
     */
    protected function createVisitorMock(): MockObject
    {
        $visitor = $this->createPartialMock(
            AbstractVisitor::class,
            [
                'prepare',
                'visitNull',
                'visitString',
                'visitBoolean',
                'visitDouble',
                'visitInteger',
                'visitDate',
                'visitDateTime',
                'visitTime',
                'visitArray',
                'visitObject'
            ]
        );

        return $visitor;
    }

    public function testObjectNavigator(): void
    {
        $object = new FakeObjectWithRelatedObject();
        $object->name = 'bob';
        $object->setWifeName('Bobella');
        $object->setMistressName('Bobofeta');
        $object->setCaught(false);

        $child = new FakeObject();
        $child->name = 'Bob Junior';
        $child2 = new FakeObject();
        $child2->name = 'Bobella Junior';

        $object->setChild($child);

        $children = [$child, $child2];
        $object->setChildren($children);

        $metadataFactory = $this->createDefaultMetadataFactory();
        $dataNavigator = new DataNavigator($metadataFactory);
        $visitor = new FakeVisitor();
        $visitor->setDataNavigator($dataNavigator);

        $data = $dataNavigator->navigate($object, $visitor);

        $this->assertInternalType('array', $data);
    }

    /**
     * Test Navigation with object
     */
    public function testNavigateObjectWithElasticsearchVisitor()
    {
        $dataNavigator = new DataNavigator($this->createDefaultMetadataFactory());

        $visitor = new ElasticsearchVisitor();
        $visitor->setDataNavigator($dataNavigator);

        $object = new FakeObjectWithRelatedObject();
        $object->name = 'bob';
        $object->setWifeName('Bobella');
        $object->setMistressName('Bobofeta');
        $object->setCaught(false);

        $dataArray = $dataNavigator->navigate($object, $visitor);

        $this->assertInternalType('array', $dataArray);
    }

    /**
     * Test Navigation with object
     */
    public function testNavigateObjectWithElasticsearchVisitorAndObjectArray()
    {
        $dataNavigator = new DataNavigator($this->createDefaultMetadataFactory());

        $visitor = new ElasticsearchVisitor();
        $visitor->setDataNavigator($dataNavigator);

        $object = new FakeObjectWithRelatedObject();
        $object->name = 'bob';
        $object->setWifeName('Bobella');
        $object->setMistressName('Bobofeta');
        $object->setCaught(false);

        $child1 = new FakeObject();
        $child1->name = 'bob junior';

        $child2 = new FakeObject();
        $child2->name = 'bob junior 2';

        $object->setChildren([$child1, $child2]);

        $dataArray = $dataNavigator->navigate($object, $visitor);

        $this->assertInternalType('array', $dataArray);
        $this->assertInternalType('array', $dataArray['children']);
        $this->assertCount(2 , $dataArray['children']);
        $this->assertEquals('bob junior' , $dataArray['children'][0]['name']);
        $this->assertEquals('bob junior 2', $dataArray['children'][1]['name']);
    }

    public function testNavigateObjectWithReverseReverseElasticsearchVisitor()
    {
        $dataNavigator = new DataNavigator($this->createDefaultMetadataFactory());

        $visitor = new ReverseElasticsearchVisitor();
        $visitor->setDataNavigator($dataNavigator);

        $object = new FakeObjectWithRelatedObject();

        $data = array(
            'name' => 'Pat',
            'wifeName' => 'Ball and Chain',
            'mistressName' => 'Lets not give that away',
            'child' => array(
                'name' => 'Pat Junior',
            ),
            'children' => [
                ['name' => 'Pat Junior 2'],
                ['name' => 'Pat Junior 3'],
            ]
        );

        $config = array(
            'type' => 'object',
            'class' => get_class($object),
            'params' => [
                'populate_object' => $object
            ]
        );

        $object = $dataNavigator->navigate($data, $visitor, $config);

        $this->assertInstanceOf(FakeObjectWithRelatedObject::class, $object);
        $this->assertInstanceOf(FakeObject::class, $object->getChild());
        $this->assertInternalType('array', $object->getChildren());
        $this->assertCount(2, $object->getChildren());
        foreach ($object->getChildren() as $child) {
            $this->assertInstanceOf(FakeObject::class, $child);
        }
    }

    /**
     * @return MetadataFactory
     */
    protected function createDefaultMetadataFactory(): MetadataFactory
    {
        $fileLocator = new FileLocator(
            ['CCT\Component\ORMElasticsearch\Tests\Fixture' => __DIR__ . '/../Fixture/config']
        );

        $yamlDriver = new YamlDriver($fileLocator);

        return new MetadataFactory($yamlDriver);
    }
}
