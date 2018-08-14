<?php

declare(strict_types=1);

namespace CCT\Component\ODMElasticsearch\Tests\Metadata;

use CCT\Component\ODMElasticsearch\Metadata\PropertyMetadata;
use CCT\Component\ODMElasticsearch\Tests\Fixture\FakeObject;
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
