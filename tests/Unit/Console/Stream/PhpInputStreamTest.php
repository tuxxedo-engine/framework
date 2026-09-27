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

namespace Unit\Console\Stream;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Console\ConsoleException;
use Tuxxedo\Console\Stream\PhpInputStream;

class PhpInputStreamTest extends TestCase
{
    public function testReadReturnsUpToRequestedBytes(): void
    {
        $stream = new PhpInputStream(
            resource: $this->memoryResourceContaining(bytes: 'hello world'),
        );

        self::assertSame(
            'hello',
            $stream->read(length: 5),
        );
    }

    public function testReadReturnsShorterChunkAtEof(): void
    {
        $stream = new PhpInputStream(
            resource: $this->memoryResourceContaining(bytes: 'hi'),
        );

        self::assertSame(
            'hi',
            $stream->read(length: 100),
        );
    }

    public function testReadLineStripsTrailingNewlineCharacters(): void
    {
        $stream = new PhpInputStream(
            resource: $this->memoryResourceContaining(bytes: "one\r\ntwo\n"),
        );

        self::assertSame(
            'one',
            $stream->readLine(),
        );

        self::assertSame(
            'two',
            $stream->readLine(),
        );
    }

    public function testReadLineReturnsNullAtEndOfStream(): void
    {
        $stream = new PhpInputStream(
            resource: $this->memoryResourceContaining(bytes: ''),
        );

        self::assertNull($stream->readLine());
    }

    public function testReadAllDrainsRemainingContent(): void
    {
        $stream = new PhpInputStream(
            resource: $this->memoryResourceContaining(bytes: 'the quick brown fox'),
        );

        self::assertSame(
            'the quick brown fox',
            $stream->readAll(),
        );
    }

    public function testCloseReleasesTheResource(): void
    {
        $resource = $this->memoryResourceContaining(bytes: 'x');
        $stream = new PhpInputStream(
            resource: $resource,
        );

        $stream->close();

        self::assertFalse(\is_resource($resource));
    }

    public function testCloseIsIdempotentOnAnAlreadyClosedResource(): void
    {
        $resource = $this->memoryResourceContaining(bytes: 'x');
        $stream = new PhpInputStream(
            resource: $resource,
        );

        $stream->close();
        $stream->close();

        self::assertFalse(\is_resource($resource));
    }

    public function testReadAfterCloseThrows(): void
    {
        $stream = new PhpInputStream(
            resource: $this->memoryResourceContaining(bytes: 'x'),
        );

        $stream->close();

        $this->expectException(ConsoleException::class);

        $stream->read(length: 1);
    }

    public function testReadLineAfterCloseThrows(): void
    {
        $stream = new PhpInputStream(
            resource: $this->memoryResourceContaining(bytes: 'x'),
        );

        $stream->close();

        $this->expectException(ConsoleException::class);

        $stream->readLine();
    }

    public function testReadAllAfterCloseThrows(): void
    {
        $stream = new PhpInputStream(
            resource: $this->memoryResourceContaining(bytes: 'x'),
        );

        $stream->close();

        $this->expectException(ConsoleException::class);

        $stream->readAll();
    }

    public function testIsTerminalReturnsFalseOnMemoryResource(): void
    {
        $stream = new PhpInputStream(
            resource: $this->memoryResourceContaining(bytes: 'x'),
        );

        self::assertFalse($stream->isTerminal);
    }

    public function testIsTerminalReturnsFalseOnClosedResource(): void
    {
        $stream = new PhpInputStream(
            resource: $this->memoryResourceContaining(bytes: 'x'),
        );

        $stream->close();

        self::assertFalse($stream->isTerminal);
    }

    /**
     * @return resource
     */
    private function memoryResourceContaining(
        string $bytes,
    ): mixed {
        $resource = \fopen('php://memory', 'r+');

        self::assertIsResource($resource);

        if ($bytes !== '') {
            \fwrite($resource, $bytes);
            \rewind($resource);
        }

        return $resource;
    }
}
