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
use Tuxxedo\Http\HttpException;

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

    public function testConstructorRejectsEmptyName(): void
    {
        $this->expectException(HttpException::class);

        new Header('', 'value');
    }

    public function testConstructorRejectsNameWithSpace(): void
    {
        $this->expectException(HttpException::class);

        new Header('Content Type', 'application/json');
    }

    public function testConstructorRejectsNameWithColon(): void
    {
        $this->expectException(HttpException::class);

        new Header('Content:Type', 'application/json');
    }

    public function testConstructorRejectsNameWithNonAscii(): void
    {
        $this->expectException(HttpException::class);

        new Header('Übermorgen', 'value');
    }

    public function testConstructorRejectsValueWithNewline(): void
    {
        $this->expectException(HttpException::class);

        new Header('X-Custom', "line1\nline2");
    }

    public function testConstructorRejectsValueWithCarriageReturn(): void
    {
        $this->expectException(HttpException::class);

        new Header('X-Custom', "line1\rline2");
    }

    public function testConstructorRejectsValueWithNullByte(): void
    {
        $this->expectException(HttpException::class);

        new Header('X-Custom', "byte\x00bad");
    }

    public function testWithValueRejectsInjectedNewline(): void
    {
        $header = new Header('X-Custom', 'safe');

        $this->expectException(HttpException::class);

        $header->withValue("bad\r\nX-Injected: yes");
    }

    public function testWithValueRejectsNullByte(): void
    {
        $header = new Header('X-Custom', 'safe');

        $this->expectException(HttpException::class);

        $header->withValue("bad\x00byte");
    }
}
