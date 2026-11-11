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
use Tuxxedo\Http\Stream\GeneratorStreamProxy;
use Tuxxedo\Http\Stream\JsonStreamFormat;
use Tuxxedo\Http\Stream\SseEvent;
use Tuxxedo\Http\Stream\Stream;
use Tuxxedo\Http\Stream\StreamException;

class StreamTest extends TestCase
{
    private static function makeStream(string ...$chunks): Stream
    {
        return new Stream(
            streamProxy: new GeneratorStreamProxy(
                static function () use ($chunks): \Generator {
                    yield from $chunks;
                },
            ),
        );
    }

    public function testClosedFalseInitially(): void
    {
        $stream = self::makeStream('hello');

        self::assertFalse($stream->closed);
    }

    public function testClosedTrueAfterClose(): void
    {
        $stream = self::makeStream('hello');
        $stream->close();

        self::assertTrue($stream->closed);
    }

    public function testAutoFlushDefaultIsFalse(): void
    {
        $stream = self::makeStream();

        self::assertFalse($stream->autoFlush);
    }

    public function testAutoFlushExplicitTrue(): void
    {
        $stream = new Stream(
            streamProxy: new GeneratorStreamProxy(
                static function (): \Generator {
                    yield from [];
                },
            ),
            autoFlush: true,
        );

        self::assertTrue($stream->autoFlush);
    }

    public function testEofDelegatesToProxy(): void
    {
        $stream = self::makeStream('hello');

        self::assertFalse($stream->eof());

        $stream->read();

        self::assertTrue($stream->eof());
    }

    public function testEofTrueWhenClosed(): void
    {
        $stream = self::makeStream('hello');
        $stream->close();

        self::assertTrue($stream->eof());
    }

    public function testGetSizeDelegatesToProxy(): void
    {
        self::assertNull(self::makeStream('hello')->getSize());
    }

    public function testGetSizeNullWhenClosed(): void
    {
        $stream = self::makeStream('hello');
        $stream->close();

        self::assertNull($stream->getSize());
    }

    public function testReadDelegatesToProxy(): void
    {
        $stream = self::makeStream('hello', 'world');

        self::assertSame('hello', $stream->read());
        self::assertSame('world', $stream->read());
    }

    public function testReadNullWhenClosed(): void
    {
        $stream = self::makeStream('hello');
        $stream->close();

        self::assertNull($stream->read());
    }

    public function testGetContentsDelegatesToProxy(): void
    {
        $stream = self::makeStream('foo', 'bar', 'baz');

        self::assertSame('foobarbaz', $stream->getContents());
    }

    public function testGetContentsEmptyWhenClosed(): void
    {
        $stream = self::makeStream('hello');
        $stream->close();

        self::assertSame('', $stream->getContents());
    }

    public function testFromGeneratorWithClosure(): void
    {
        $stream = Stream::fromGenerator(
            static function (): \Generator {
                yield 'hello';
            },
        );

        self::assertSame('hello', $stream->read());
    }

    public function testFromGeneratorWithGenerator(): void
    {
        $generator = (static function (): \Generator {
            yield 'hello';
        })();

        $stream = Stream::fromGenerator($generator);

        self::assertSame('hello', $stream->read());
    }

    public function testFromGeneratorAutoFlushDefaultIsTrue(): void
    {
        $stream = Stream::fromGenerator(
            static function (): \Generator {
                yield from [];
            },
        );

        self::assertTrue($stream->autoFlush);
    }

    public function testCopyToWritesTheRemainingContent(): void
    {
        $path = \tempnam(\sys_get_temp_dir(), 'stream_copy_');

        self::assertIsString($path);

        try {
            $stream = Stream::fromGenerator(
                static function (): \Generator {
                    yield 'foo';
                    yield 'bar';
                    yield 'baz';
                },
            );

            self::assertSame('foo', $stream->read());

            $stream->copyTo($path);

            self::assertSame('barbaz', \file_get_contents($path));
            self::assertTrue($stream->eof());
        } finally {
            \unlink($path);
        }
    }

    public function testCopyToUnwritablePathThrowsStreamException(): void
    {
        try {
            Stream::fromGenerator(
                static function (): \Generator {
                    yield 'x';
                },
            )->copyTo('/nonexistent/path/that/does/not/exist.txt');

            self::fail('Expected StreamException was not thrown');
        } catch (StreamException $exception) {
            self::assertStringContainsString('could not be opened for writing', $exception->getMessage());
        }
    }

    public function testFromCsvWithClosure(): void
    {
        $stream = Stream::fromCsv(
            generator: static function (): \Generator {
                yield [
                    'a',
                    'b',
                ];
            },
        );

        self::assertSame("a,b\n", $stream->read());
    }

    public function testFromCsvWithGenerator(): void
    {
        $generator = (static function (): \Generator {
            yield [
                'a',
                'b',
            ];
        })();

        $stream = Stream::fromCsv(
            generator: $generator,
        );

        self::assertSame("a,b\n", $stream->read());
    }

    public function testFromCsvAutoFlushDefaultIsTrue(): void
    {
        $stream = Stream::fromCsv(
            generator: static function (): \Generator {
                yield from [];
            },
        );

        self::assertTrue($stream->autoFlush);
    }

    public function testFromCsvExposesCsvContentTypeHeader(): void
    {
        $stream = Stream::fromCsv(
            generator: static function (): \Generator {
                yield from [];
            },
        );

        self::assertCount(1, $stream->headers);
        self::assertSame('Content-Type', $stream->headers[0]->name);
        self::assertSame('text/csv; charset=utf-8', $stream->headers[0]->value);
    }

    public function testFromCsvIncludesColumnsRowInContents(): void
    {
        $stream = Stream::fromCsv(
            generator: static function (): \Generator {
                yield [
                    'value-a',
                    'value-b',
                ];
            },
            columns: [
                'col-a',
                'col-b',
            ],
        );

        self::assertSame("col-a,col-b\nvalue-a,value-b\n", $stream->getContents());
    }

    public function testFromCsvPropagatesSeparatorOption(): void
    {
        $stream = Stream::fromCsv(
            generator: static function (): \Generator {
                yield [
                    'a',
                    'b',
                ];
            },
            separator: ';',
        );

        self::assertSame("a;b\n", $stream->read());
    }

    public function testFromCsvPropagatesEnclosureOption(): void
    {
        $stream = Stream::fromCsv(
            generator: static function (): \Generator {
                yield [
                    'a,b',
                ];
            },
            enclosure: '\'',
        );

        self::assertSame("'a,b'\n", $stream->read());
    }

    public function testFromCsvPropagatesEolOption(): void
    {
        $stream = Stream::fromCsv(
            generator: static function (): \Generator {
                yield [
                    'a',
                ];
            },
            eol: "\r\n",
        );

        self::assertSame("a\r\n", $stream->read());
    }

    public function testFromSseWithClosure(): void
    {
        $stream = Stream::fromSse(
            generator: static function (): \Generator {
                yield SseEvent::create(
                    data: 'hello',
                );
            },
        );

        self::assertSame("data: hello\n\n", $stream->read());
    }

    public function testFromSseWithGenerator(): void
    {
        $generator = (static function (): \Generator {
            yield SseEvent::create(
                data: 'hello',
            );
        })();

        $stream = Stream::fromSse(
            generator: $generator,
        );

        self::assertSame("data: hello\n\n", $stream->read());
    }

    public function testFromSseAutoFlushDefaultIsTrue(): void
    {
        $stream = Stream::fromSse(
            generator: static function (): \Generator {
                yield from [];
            },
        );

        self::assertTrue($stream->autoFlush);
    }

    public function testFromSseExposesEventStreamHeaders(): void
    {
        $stream = Stream::fromSse(
            generator: static function (): \Generator {
                yield from [];
            },
        );

        self::assertCount(2, $stream->headers);
        self::assertSame('Content-Type', $stream->headers[0]->name);
        self::assertSame('text/event-stream', $stream->headers[0]->value);
        self::assertSame('Cache-Control', $stream->headers[1]->name);
        self::assertSame('no-cache', $stream->headers[1]->value);
    }

    public function testFromSseGetContentsFormatsAllEvents(): void
    {
        $stream = Stream::fromSse(
            generator: static function (): \Generator {
                yield SseEvent::create(
                    data: 'first',
                    id: 'evt-1',
                );

                yield SseEvent::keepalive();

                yield SseEvent::create(
                    data: 'second',
                );
            },
        );

        self::assertSame(
            "id: evt-1\ndata: first\n\n: keepalive\n\ndata: second\n\n",
            $stream->getContents(),
        );
    }

    public function testFromJsonWithClosure(): void
    {
        $stream = Stream::fromJson(
            generator: static function (): \Generator {
                yield [
                    'a' => 1,
                ];
            },
        );

        self::assertSame("{\"a\":1}\n", $stream->read());
    }

    public function testFromJsonWithGenerator(): void
    {
        $generator = (static function (): \Generator {
            yield [
                'a' => 1,
            ];
        })();

        $stream = Stream::fromJson(
            generator: $generator,
        );

        self::assertSame("{\"a\":1}\n", $stream->read());
    }

    public function testFromJsonAutoFlushDefaultIsTrue(): void
    {
        $stream = Stream::fromJson(
            generator: static function (): \Generator {
                yield from [];
            },
        );

        self::assertTrue($stream->autoFlush);
    }

    public function testFromJsonExposesJsonlContentTypeByDefault(): void
    {
        $stream = Stream::fromJson(
            generator: static function (): \Generator {
                yield from [];
            },
        );

        self::assertCount(1, $stream->headers);
        self::assertSame('Content-Type', $stream->headers[0]->name);
        self::assertSame('application/x-ndjson', $stream->headers[0]->value);
    }

    public function testFromJsonExposesRfc7464ContentTypeWhenFormatSet(): void
    {
        $stream = Stream::fromJson(
            generator: static function (): \Generator {
                yield from [];
            },
            format: JsonStreamFormat::RFC7464,
        );

        self::assertSame('application/json-seq', $stream->headers[0]->value);
    }

    public function testFromJsonGetContentsFormatsJsonlItems(): void
    {
        $stream = Stream::fromJson(
            generator: static function (): \Generator {
                yield [
                    'a' => 1,
                ];

                yield [
                    'b' => 2,
                ];
            },
        );

        self::assertSame("{\"a\":1}\n{\"b\":2}\n", $stream->getContents());
    }

    public function testFromJsonGetContentsFormatsRfc7464Items(): void
    {
        $stream = Stream::fromJson(
            generator: static function (): \Generator {
                yield [
                    'a' => 1,
                ];

                yield [
                    'b' => 2,
                ];
            },
            format: JsonStreamFormat::RFC7464,
        );

        self::assertSame("\x1e{\"a\":1}\n\x1e{\"b\":2}\n", $stream->getContents());
    }
}
