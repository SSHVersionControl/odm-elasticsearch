<?php

declare(strict_types=1);

namespace CCT\Component\ORMElasticsearch\Tests\Metadata;

use CCT\Component\ORMElasticsearch\Metadata\PropertyMetadata;
use CCT\Component\ORMElasticsearch\Tests\Fixture\FakeObject;
use PHPUnit\Framework\TestCase;

class ClassMetadataTest extends TestCase
{
    public function testSerialization(): void
    {
        $meta = new PropertyMetadata(FakeObject::class, 'name');
        $restoredMeta = unserialize(serialize($meta));
        self::assertEquals($meta, $restoredMeta);
    }
}
