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

namespace Unit\Http\Client\Body;

use Fixture\Http\Request\Context\BodyContextFixture;
use PHPUnit\Framework\TestCase;
use Support\Serializer\SerializerManagerFactory;
use Tuxxedo\Http\Client\Body\JsonBody;

class JsonBodyTest extends TestCase
{
    public function testEncodesObjects(): void
    {
        self::assertSame(
            '{"name":"John","age":30}',
            new JsonBody(new BodyContextFixture('John', 30))->encode(SerializerManagerFactory::create()),
        );
    }

    public function testEncodesArrays(): void
    {
        self::assertSame(
            '{"query":"a/b","limit":5}',
            new JsonBody(
                [
                    'query' => 'a/b',
                    'limit' => 5,
                ],
                flags: \JSON_UNESCAPED_SLASHES,
            )->encode(SerializerManagerFactory::create()),
        );
    }

    public function testContentType(): void
    {
        self::assertSame('application/json', new JsonBody([])->contentType);
        self::assertSame('application/vnd.api+json', new JsonBody([], contentType: 'application/vnd.api+json')->contentType);
    }
}
