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

namespace Unit\Http\Request\Context;

use Fixture\Http\Request\Context\BodyContextFixture;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Request\Context\EnvironmentBodyContext;

class EnvironmentBodyContextTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        $this->tempFile = \tempnam(\sys_get_temp_dir(), 'body_context_test_');
    }

    protected function tearDown(): void
    {
        if (\file_exists($this->tempFile)) {
            \unlink($this->tempFile);
        }
    }

    private function makeContext(
        string $content,
    ): EnvironmentBodyContext {
        \file_put_contents($this->tempFile, $content);

        return new EnvironmentBodyContext(
            streamInputSource: $this->tempFile,
        );
    }

    public function testGetStreamIsReadable(): void
    {
        $stream = $this->makeContext('hello')->getStream();

        self::assertSame('hello', \stream_get_contents($stream));
    }

    public function testGetStreamThrowsForInvalidSource(): void
    {
        $this->expectException(HttpException::class);

        (new EnvironmentBodyContext(streamInputSource: '/nonexistent/path'))->getStream();
    }

    public function testGetRawReadsStreamContent(): void
    {
        self::assertSame('hello world', $this->makeContext('hello world')->getRaw());
    }

    public function testGetJsonDecodesJsonObject(): void
    {
        $result = $this->makeContext('{"key":"value"}')->getJson();

        self::assertInstanceOf(\stdClass::class, $result);
        self::assertSame('value', $result->key);
    }

    public function testGetJsonAssociativeReturnsArray(): void
    {
        $result = $this->makeContext('{"key":"value"}')->getJson(
            associative: true,
        );

        self::assertSame(
            [
                'key' => 'value',
            ],
            $result,
        );
    }

    public function testJsonMapToMapsObjectJson(): void
    {
        $result = $this->makeContext('{"name":"John","age":30}')->jsonMapTo(
            className: BodyContextFixture::class,
        );

        self::assertInstanceOf(BodyContextFixture::class, $result);
        self::assertSame('John', $result->name);
        self::assertSame(30, $result->age);
    }

    public function testJsonMapToMapsFromJsonArray(): void
    {
        $result = $this->makeContext('[]')->jsonMapTo(
            className: BodyContextFixture::class,
        );

        self::assertInstanceOf(BodyContextFixture::class, $result);
    }

    public function testJsonMapToThrowsForScalarJson(): void
    {
        $this->expectException(HttpException::class);

        $this->makeContext('"just a string"')->jsonMapTo(
            className: BodyContextFixture::class,
        );
    }

    public function testJsonMapToArrayOfMapsArrayJson(): void
    {
        $result = $this->makeContext(
            '[{"name":"John","age":30},{"name":"Jane","age":25}]',
        )->jsonMapToArrayOf(
            className: BodyContextFixture::class,
        );

        self::assertCount(2, $result);
        self::assertInstanceOf(BodyContextFixture::class, $result[0]);
        self::assertSame('John', $result[0]->name);
        self::assertSame('Jane', $result[1]->name);
    }

    public function testJsonMapToArrayOfThrowsForNonArray(): void
    {
        $this->expectException(HttpException::class);

        $this->makeContext('{"name":"John"}')->jsonMapToArrayOf(
            className: BodyContextFixture::class,
        );
    }
}
