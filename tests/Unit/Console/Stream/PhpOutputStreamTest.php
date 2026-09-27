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
use Tuxxedo\Console\Stream\PhpOutputStream;

class PhpOutputStreamTest extends TestCase
{
    public function testWriteAppendsBytesToUnderlyingResource(): void
    {
        $resource = $this->memoryResource();
        $stream = new PhpOutputStream(
            resource: $resource,
        );

        $stream->write(bytes: 'hello');

        self::assertSame(
            'hello',
            $this->rewindAndRead($resource),
        );
    }

    public function testWriteWithEmptyStringIsNoop(): void
    {
        $resource = $this->memoryResource();
        $stream = new PhpOutputStream(
            resource: $resource,
        );

        $stream->write(bytes: '');

        self::assertSame(
            '',
            $this->rewindAndRead($resource),
        );
    }

    public function testWriteCallsAccumulate(): void
    {
        $resource = $this->memoryResource();
        $stream = new PhpOutputStream(
            resource: $resource,
        );

        $stream->write(bytes: 'one');
        $stream->write(bytes: '-two');

        self::assertSame(
            'one-two',
            $this->rewindAndRead($resource),
        );
    }

    public function testCloseReleasesTheResource(): void
    {
        $resource = $this->memoryResource();
        $stream = new PhpOutputStream(
            resource: $resource,
        );

        $stream->close();

        self::assertFalse(\is_resource($resource));
    }

    public function testCloseIsIdempotentOnAnAlreadyClosedResource(): void
    {
        $resource = $this->memoryResource();
        $stream = new PhpOutputStream(
            resource: $resource,
        );

        $stream->close();
        $stream->close();

        self::assertFalse(\is_resource($resource));
    }

    public function testWriteAfterCloseThrows(): void
    {
        $stream = new PhpOutputStream(
            resource: $this->memoryResource(),
        );

        $stream->close();

        $this->expectException(ConsoleException::class);

        $stream->write(bytes: 'nope');
    }

    public function testIsTerminalReturnsFalseOnMemoryResource(): void
    {
        $stream = new PhpOutputStream(
            resource: $this->memoryResource(),
        );

        self::assertFalse($stream->isTerminal);
    }

    public function testIsTerminalReturnsFalseOnClosedResource(): void
    {
        $stream = new PhpOutputStream(
            resource: $this->memoryResource(),
        );

        $stream->close();

        self::assertFalse($stream->isTerminal);
    }

    /**
     * @return resource
     */
    private function memoryResource(): mixed
    {
        $resource = \fopen('php://memory', 'w+');

        self::assertIsResource($resource);

        return $resource;
    }

    /**
     * @param resource $resource
     */
    private function rewindAndRead(
        mixed $resource,
    ): string {
        \rewind($resource);

        $contents = \stream_get_contents($resource);

        self::assertIsString($contents);

        return $contents;
    }
}
