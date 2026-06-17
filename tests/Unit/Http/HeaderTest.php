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

namespace Unit\Http;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Http\Header;

class HeaderTest extends TestCase
{
    public function testConstructor(): void
    {
        $header = new Header('Content-Type', 'application/json');

        self::assertSame('Content-Type', $header->name);
        self::assertSame('application/json', $header->value);
    }

    public function testIs(): void
    {
        $header = new Header('Content-Length', '1000');

        self::assertTrue($header->is('content-length'));
        self::assertTrue($header->is('Content-Length'));
    }

    public function testWithValueReturnsNewInstance(): void
    {
        $header = new Header('Content-Type', 'application/json');
        $updated = $header->withValue('text/html');

        self::assertNotSame($header, $updated);
    }

    public function testWithValueChangesValue(): void
    {
        $header = new Header('Content-Type', 'application/json');
        $updated = $header->withValue('text/html');

        self::assertSame('text/html', $updated->value);
    }

    public function testWithValuePreservesName(): void
    {
        $header = new Header('Content-Type', 'application/json');
        $updated = $header->withValue('text/html');

        self::assertSame('Content-Type', $updated->name);
    }
}
