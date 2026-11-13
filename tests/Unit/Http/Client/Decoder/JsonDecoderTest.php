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
use Fixture\Serializer\Validated;
use PHPUnit\Framework\TestCase;
use Support\Serializer\SerializerManagerFactory;
use Tuxxedo\Http\Client\ClientBodyContext;
use Tuxxedo\Http\Client\Decoder\JsonDecoder;
use Tuxxedo\Validator\ValidationException;

class JsonDecoderTest extends TestCase
{
    private static function body(
        string $content,
        string $contentType = 'application/json',
    ): ClientBodyContext {
        return new ClientBodyContext(
            serializerManager: SerializerManagerFactory::create(),
            body: $content,
            contentType: $contentType,
        );
    }

    public function testSupports(): void
    {
        self::assertTrue(new JsonDecoder()->supports(self::body('')));
        self::assertFalse(new JsonDecoder()->supports(self::body('', 'text/plain')));
    }

    public function testMap(): void
    {
        $result = new JsonDecoder()->map(
            body: self::body('{"data":{"name":"John","age":30}}'),
            className: BodyContextFixture::class,
            key: 'data',
            only: [
                'name',
            ],
        );

        self::assertSame('John', $result->name);
        self::assertSame(0, $result->age);
    }

    public function testMapList(): void
    {
        $result = new JsonDecoder()->mapList(self::body('[{"name":"John"},{"name":"Jane"}]'), BodyContextFixture::class);

        self::assertCount(2, $result);
        self::assertSame('Jane', $result[1]->name);
    }

    public function testSkipUnknownKeysIsPassedOn(): void
    {
        $this->expectException(ValidationException::class);

        new JsonDecoder()->map(
            body: self::body('{"name":"John","extra":true}'),
            className: BodyContextFixture::class,
            skipUnknownKeys: false,
        );
    }

    public function testSkipValidationIsPassedOn(): void
    {
        self::assertSame(
            -2,
            new JsonDecoder()->map(
                body: self::body('{"quantity":-2}'),
                className: Validated::class,
                skipValidation: true,
            )->quantity,
        );
    }
}
