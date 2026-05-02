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

namespace Unit\Http\Response\Stream;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Http\Response\Stream\ResourceStreamProxy;

class ResourceStreamProxyTest extends TestCase
{
    /**
     * @return resource
     */
    private static function memoryResource(string $content = ''): mixed
    {
        $resource = \fopen('php://memory', 'r+b');

        self::assertIsResource($resource);

        if ($content !== '') {
            \fwrite($resource, $content);
            \rewind($resource);
        }

        return $resource;
    }

    public function testEofFalseWhenContentAvailable(): void
    {
        $proxy = new ResourceStreamProxy(self::memoryResource('hello'));

        self::assertFalse($proxy->eof());
    }

    public function testEofTrueAfterReadingAllContent(): void
    {
        $proxy = new ResourceStreamProxy(self::memoryResource('hello'));

        $proxy->read();

        self::assertTrue($proxy->eof());
    }

    public function testGetSizeNullOnEmptyResource(): void
    {
        $proxy = new ResourceStreamProxy(self::memoryResource());

        self::assertNull($proxy->getSize());
    }

    public function testGetSizeReturnsContentLength(): void
    {
        $proxy = new ResourceStreamProxy(self::memoryResource('hello world'));

        self::assertSame(11, $proxy->getSize());
    }

    public function testReadReturnsContent(): void
    {
        $proxy = new ResourceStreamProxy(self::memoryResource('hello'));

        self::assertSame('hello', $proxy->read());
    }

    public function testReadReturnsNullWhenExhausted(): void
    {
        $proxy = new ResourceStreamProxy(self::memoryResource('hello'));

        $proxy->read();

        self::assertNull($proxy->read());
    }

    public function testReadRespectsChunkSize(): void
    {
        $proxy = new ResourceStreamProxy(self::memoryResource('hello world'), chunkSize: 5);

        self::assertSame('hello', $proxy->read());
        self::assertSame(' worl', $proxy->read());
        self::assertSame('d', $proxy->read());
    }

    public function testContentsReturnsAllContent(): void
    {
        $proxy = new ResourceStreamProxy(self::memoryResource('hello world'));

        self::assertSame('hello world', $proxy->contents());
    }

    public function testContentsRewoundsBeforeReading(): void
    {
        $resource = self::memoryResource('hello world');

        // advance position part-way through
        \fread($resource, 5);

        $proxy = new ResourceStreamProxy($resource);

        self::assertSame('hello world', $proxy->contents());
    }

    public function testContentsOnEmptyResourceReturnsEmptyString(): void
    {
        $proxy = new ResourceStreamProxy(self::memoryResource());

        self::assertSame('', $proxy->contents());
    }
}
