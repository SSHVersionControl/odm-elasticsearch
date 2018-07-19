<?php

declare(strict_types=1);

namespace CCT\Component\ORMElasticsearch\Tests\Transformer;

use CCT\Component\ORMElasticsearch\Metadata\Driver\YamlDriver;
use CCT\Component\ORMElasticsearch\Tests\Fixture\FakeObject;
use CCT\Component\ORMElasticsearch\Tests\Fixture\FakeObjectWithRelatedObject;
use CCT\Component\ORMElasticsearch\Tests\Fixture\Visitor\FakeVisitor;
use CCT\Component\ORMElasticsearch\Transformer\DataNavigator;
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
            VisitorInterface::class,
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

        $fileLocator = new FileLocator(
            ['CCT\Component\ORMElasticsearch\Tests\Fixture' => __DIR__ . '/../Fixture/config']
        );

        $yamlDriver = new YamlDriver($fileLocator);

        $metadataFactory = new MetadataFactory($yamlDriver);
        $dataNavigator = new DataNavigator($metadataFactory);
        $visitor = new FakeVisitor();

        $data = $dataNavigator->navigate($object, $visitor);

        $this->assertInternalType('array', $data);
    }
}
