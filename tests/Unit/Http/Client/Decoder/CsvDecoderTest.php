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
use Tuxxedo\Http\Client\Decoder\CsvDecoder;
use Tuxxedo\Http\HttpException;

class CsvDecoderTest extends TestCase
{
    private static function body(
        string $content,
        string $contentType = 'text/csv',
    ): ClientBodyContext {
        return new ClientBodyContext(
            serializerManager: SerializerManagerFactory::create(),
            body: $content,
            contentType: $contentType,
        );
    }

    public function testSupports(): void
    {
        self::assertTrue(new CsvDecoder()->supports(self::body('')));
        self::assertFalse(new CsvDecoder()->supports(self::body('', 'text/plain')));
    }

    public function testMapList(): void
    {
        $result = new CsvDecoder(separator: ';', enclosure: "'")->mapList(
            self::body("name;age\n'Doe; John';30\n"),
            BodyContextFixture::class,
        );

        self::assertCount(1, $result);
        self::assertSame('Doe; John', $result[0]->name);
        self::assertSame(30, $result[0]->age);
    }

    public function testMapIsNotSupported(): void
    {
        $this->expectException(HttpException::class);

        new CsvDecoder()->map(self::body("name\nJohn\n"), BodyContextFixture::class);
    }
}
