<?php

declare(strict_types=1);

namespace CCT\Component\ODMElasticsearch\Tests\Transformer\Visitor;

use CCT\Component\ODMElasticsearch\Transformer\Exception\TransformationFailedException;
use CCT\Component\ODMElasticsearch\Transformer\Visitor\ReverseElasticsearchVisitor;
use PHPUnit\Framework\TestCase;

class ReverseElasticsearchVisitorTest extends TestCase
{
    /**
     * @dataProvider dateTimeDataProvider
     */
    public function testVisitDateTimeWithTimeFormatsShouldReturnDateTime($data)
    {
        $reverseVisitor = new ReverseElasticsearchVisitor();
        $dateTime = $reverseVisitor->visitDateTime($data, []);

        $this->assertInstanceOf(\DateTimeInterface::class, $dateTime);
    }

    /**
     * Valid Date Time Data Provider
     *
     * @return array
     */
    public function dateTimeDataProvider()
    {
        return [
            //[1534332695],
            [(1534332695 * 1000)],
           // [''],
            //['20180507'],
        ];
    }

    /**
     * Test null value
     */
    public function testVisitDateTimeWithNullShouldReturnNull()
    {
        $reverseVisitor = new ReverseElasticsearchVisitor();
        $dateTime = $reverseVisitor->visitDateTime(null, []);

        $this->assertNull($dateTime);
    }

    /**
     * @param $invalidDate
     *
     * @dataProvider invalidDateTimeDataProvider
     */
    public function testVisitDateTimeWithInvalidDateShouldShouldThrowTransformationFailedException($invalidDate)
    {
        $this->expectException(TransformationFailedException::class);

        $reverseVisitor = new ReverseElasticsearchVisitor();
        $dateTime = $reverseVisitor->visitDateTime($invalidDate, []);

        $this->assertNull($dateTime);
    }

    public function invalidDateTimeDataProvider()
    {
        return [
            ['20180507fdfdsffd df sdf s'],
            ['not a date'],
            ['20012-43-46'],
        ];
    }
}
