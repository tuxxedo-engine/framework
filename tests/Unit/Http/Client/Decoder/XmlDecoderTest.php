<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2026 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Unit\Http\Client\Decoder;

use Fixture\Http\Request\Context\BodyContextFixture;
use PHPUnit\Framework\TestCase;
use Support\Serializer\SerializerManagerFactory;
use Tuxxedo\Http\Client\ClientBodyContext;
use Tuxxedo\Http\Client\Decoder\XmlDecoder;

class XmlDecoderTest extends TestCase
{
    private static function body(
        string $content,
        string $contentType = 'application/xml',
    ): ClientBodyContext {
        return new ClientBodyContext(
            serializerManager: SerializerManagerFactory::create(),
            body: $content,
            contentType: $contentType,
        );
    }

    public function testSupports(): void
    {
        self::assertTrue(new XmlDecoder()->supports(self::body('')));
        self::assertFalse(new XmlDecoder()->supports(self::body('', 'application/json')));
    }

    public function testMap(): void
    {
        $result = new XmlDecoder()->map(self::body('<person><name>John</name><age>30</age></person>'), BodyContextFixture::class);

        self::assertSame('John', $result->name);
        self::assertSame(30, $result->age);
    }

    public function testMapList(): void
    {
        $result = new XmlDecoder()->mapList(
            self::body('<people><person><name>John</name></person><person><name>Jane</name></person></people>'),
            BodyContextFixture::class,
        );

        self::assertCount(2, $result);
        self::assertSame('Jane', $result[1]->name);
    }
}
