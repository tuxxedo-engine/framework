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

use PHPUnit\Framework\TestCase;
use Support\Serializer\SerializerManagerFactory;
use Tuxxedo\Http\Client\Body\RawBody;
use Tuxxedo\Http\Stream\ResourceStream;

class RawBodyTest extends TestCase
{
    public function testEncodesStringsAsIs(): void
    {
        self::assertSame('bytes', new RawBody('bytes')->encode(SerializerManagerFactory::create()));
    }

    public function testEncodesStreamsAsIs(): void
    {
        $stream = ResourceStream::fromTemporary();

        self::assertSame($stream, new RawBody($stream)->encode(SerializerManagerFactory::create()));
    }

    public function testContentType(): void
    {
        self::assertSame('application/octet-stream', new RawBody('')->contentType);
        self::assertSame('text/plain', new RawBody('', contentType: 'text/plain')->contentType);
    }
}
