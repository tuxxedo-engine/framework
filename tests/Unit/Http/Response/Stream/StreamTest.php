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
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Response\Stream\GeneratorStreamProxy;
use Tuxxedo\Http\Response\Stream\JsonStreamFormat;
use Tuxxedo\Http\Response\Stream\SseEvent;
use Tuxxedo\Http\Response\Stream\Stream;

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
        $resource = \fopen('php://memory', 'r+b');

        self::assertIsResource($resource);

        \fwrite($resource, 'hello world');

        $stream = Stream::fromResource($resource);

        self::assertSame(11, $stream->getSize());
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

    public function testFromResource(): void
    {
        $resource = \fopen('php://memory', 'r+b');

        self::assertIsResource($resource);

        \fwrite($resource, 'hello');
        \rewind($resource);

        $stream = Stream::fromResource($resource);

        self::assertSame('hello', $stream->read());
    }

    public function testFromResourceAutoFlushDefaultIsFalse(): void
    {
        $resource = \fopen('php://memory', 'r+b');

        self::assertIsResource($resource);

        $stream = Stream::fromResource($resource);

        self::assertFalse($stream->autoFlush);
    }

    public function testFromFileReadsContent(): void
    {
        $path = \tempnam(\sys_get_temp_dir(), 'engine_test_');

        self::assertNotFalse($path);

        \file_put_contents($path, 'hello from file');

        try {
            $stream = Stream::fromFile($path);

            self::assertSame('hello from file', $stream->getContents());
        } finally {
            \unlink($path);
        }
    }

    public function testFromFileAutoFlushDefaultIsFalse(): void
    {
        $path = \tempnam(\sys_get_temp_dir(), 'engine_test_');

        self::assertNotFalse($path);

        \file_put_contents($path, '');

        try {
            $stream = Stream::fromFile($path);

            self::assertFalse($stream->autoFlush);
        } finally {
            \unlink($path);
        }
    }

    public function testFromFileInvalidPathThrowsHttpException(): void
    {
        self::expectException(HttpException::class);

        Stream::fromFile('/nonexistent/path/that/does/not/exist.txt');
    }

    public function testFromTemporary(): void
    {
        $stream = Stream::fromTemporary();

        self::assertFalse($stream->closed);
        self::assertFalse($stream->autoFlush);
    }

    public function testFromTemporaryNullMaxMemory(): void
    {
        $stream = Stream::fromTemporary(maxMemory: null);

        self::assertFalse($stream->closed);
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
