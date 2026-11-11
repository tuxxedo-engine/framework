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

namespace Unit\Http\Stream;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Http\Stream\ResourceStream;
use Tuxxedo\Http\Stream\StreamException;

class ResourceStreamTest extends TestCase
{
    /**
     * @param positive-int $chunkSize
     */
    private static function makeStream(
        string $content = '',
        int $chunkSize = 8192,
    ): ResourceStream {
        $stream = ResourceStream::fromTemporary(
            chunkSize: $chunkSize,
        );

        if ($content !== '') {
            $stream->write($content);
            $stream->rewind();
        }

        return $stream;
    }

    public function testWriteRewindAndRead(): void
    {
        $stream = self::makeStream('hello world');

        self::assertFalse($stream->closed);
        self::assertFalse($stream->autoFlush);
        self::assertSame([], $stream->headers);
        self::assertSame(11, $stream->getSize());
        self::assertSame('hello world', $stream->read());
        self::assertNull($stream->read());
        self::assertTrue($stream->eof());
    }

    public function testReadRespectsChunkSize(): void
    {
        $stream = self::makeStream(
            content: 'hello world',
            chunkSize: 5,
        );

        self::assertSame('hello', $stream->read());
        self::assertSame(' worl', $stream->read());
        self::assertSame('d', $stream->read());
        self::assertNull($stream->read());
    }

    public function testGetContentsReturnsTheRestFromTheCurrentPosition(): void
    {
        $stream = self::makeStream(
            content: 'hello world',
            chunkSize: 5,
        );

        self::assertSame('hello', $stream->read());
        self::assertSame(' world', $stream->getContents());
        self::assertSame('', $stream->getContents());
    }

    public function testGetSizeZeroOnEmptyStream(): void
    {
        self::assertSame(0, self::makeStream()->getSize());
        self::assertSame(0, ResourceStream::fromTemporary(maxMemory: null)->getSize());
    }

    public function testPipes(): void
    {
        $pipes = [];
        $process = \proc_open(
            [
                \PHP_BINARY,
                '-r',
                '',
            ],
            [
                [
                    'pipe',
                    'r',
                ],
            ],
            $pipes,
        );

        self::assertIsResource($process);

        try {
            $stream = ResourceStream::fromResource($pipes[0]);

            self::assertNull($stream->getSize());

            try {
                $stream->rewind();

                self::fail('Expected StreamException was not thrown');
            } catch (StreamException $exception) {
                self::assertStringContainsString('cannot be rewound', $exception->getMessage());
            }
        } finally {
            \fclose($pipes[0]);
            \proc_close($process);
        }
    }

    public function testFromFile(): void
    {
        $path = \tempnam(\sys_get_temp_dir(), 'resource_stream_');

        self::assertIsString($path);

        \file_put_contents($path, 'hello from file');

        try {
            $stream = ResourceStream::fromFile(
                path: $path,
                autoFlush: true,
            );

            self::assertTrue($stream->autoFlush);
            self::assertSame(15, $stream->getSize());
            self::assertSame('hello from file', $stream->getContents());

            try {
                $stream->write('more');

                self::fail('Expected StreamException was not thrown');
            } catch (StreamException $exception) {
                self::assertStringContainsString('could not be written', $exception->getMessage());
            }
        } finally {
            \unlink($path);
        }
    }

    public function testFromFileInvalidPathThrows(): void
    {
        try {
            ResourceStream::fromFile('/nonexistent/path/that/does/not/exist.txt');

            self::fail('Expected StreamException was not thrown');
        } catch (StreamException $exception) {
            self::assertStringContainsString('/nonexistent/path/that/does/not/exist.txt', $exception->getMessage());
        }
    }

    public function testCopyTo(): void
    {
        $path = \tempnam(\sys_get_temp_dir(), 'resource_stream_copy_');

        self::assertIsString($path);

        try {
            self::makeStream('hello world')->copyTo($path);

            self::assertSame('hello world', \file_get_contents($path));
        } finally {
            \unlink($path);
        }
    }

    public function testCopyToUnwritablePathThrows(): void
    {
        try {
            self::makeStream('x')->copyTo('/nonexistent/path/that/does/not/exist.txt');

            self::fail('Expected StreamException was not thrown');
        } catch (StreamException $exception) {
            self::assertStringContainsString('could not be opened for writing', $exception->getMessage());
        }
    }

    public function testClosedStream(): void
    {
        $stream = self::makeStream('hello');

        $stream->close();

        self::assertTrue($stream->closed);
        self::assertTrue($stream->eof());
        self::assertNull($stream->getSize());
        self::assertNull($stream->read());
        self::assertSame('', $stream->getContents());

        foreach (
            [
                static fn () => $stream->write('x'),
                static fn () => $stream->rewind(),
            ] as $operation
        ) {
            try {
                $operation();

                self::fail('Expected StreamException was not thrown');
            } catch (StreamException $exception) {
                self::assertStringContainsString('closed', $exception->getMessage());
            }
        }
    }
}
