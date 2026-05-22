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
use Tuxxedo\Http\Response\PrefersHeadersInterface;
use Tuxxedo\Http\Response\Stream\JsonStreamFormat;
use Tuxxedo\Http\Response\Stream\JsonStreamProxy;
use Tuxxedo\Http\Response\Stream\StreamProxyInterface;

class JsonStreamProxyTest extends TestCase
{
    /**
     * @param \Closure(): \Generator<mixed>|\Generator<mixed> $generator
     */
    private static function makeProxy(
        \Closure|\Generator $generator,
        JsonStreamFormat $format = JsonStreamFormat::JSONL,
    ): StreamProxyInterface {
        return new JsonStreamProxy(
            generator: $generator,
            format: $format,
        );
    }

    public function testConstructorWithClosure(): void
    {
        $proxy = new JsonStreamProxy(
            generator: static function (): \Generator {
                yield [
                    'foo' => 'bar',
                ];
            },
        );

        self::assertSame("{\"foo\":\"bar\"}\n", $proxy->read());
    }

    public function testConstructorWithGenerator(): void
    {
        $generator = (static function (): \Generator {
            yield [
                'foo' => 'bar',
            ];
        })();

        $proxy = new JsonStreamProxy(
            generator: $generator,
        );

        self::assertSame("{\"foo\":\"bar\"}\n", $proxy->read());
    }

    public function testImplementsPrefersHeadersInterface(): void
    {
        $proxy = new JsonStreamProxy(
            generator: static function (): \Generator {
                yield from [];
            },
        );

        self::assertInstanceOf(PrefersHeadersInterface::class, $proxy);
    }

    public function testHeadersExposeJsonlContentTypeByDefault(): void
    {
        $proxy = new JsonStreamProxy(
            generator: static function (): \Generator {
                yield from [];
            },
        );

        self::assertCount(1, $proxy->headers);
        self::assertSame('Content-Type', $proxy->headers[0]->name);
        self::assertSame('application/x-ndjson', $proxy->headers[0]->value);
    }

    public function testHeadersExposeRfc7464ContentTypeWhenFormatSet(): void
    {
        $proxy = new JsonStreamProxy(
            generator: static function (): \Generator {
                yield from [];
            },
            format: JsonStreamFormat::RFC7464,
        );

        self::assertCount(1, $proxy->headers);
        self::assertSame('Content-Type', $proxy->headers[0]->name);
        self::assertSame('application/json-seq', $proxy->headers[0]->value);
    }

    public function testGetSizeAlwaysReturnsNull(): void
    {
        $proxy = self::makeProxy(
            generator: static function (): \Generator {
                yield [
                    'a' => 1,
                ];
            },
        );

        self::assertNull($proxy->getSize());
    }

    public function testEofFalseInitiallyWhenGeneratorHasItems(): void
    {
        $proxy = new JsonStreamProxy(
            generator: static function (): \Generator {
                yield [
                    'a' => 1,
                ];
            },
        );

        self::assertFalse($proxy->eof());
    }

    public function testEofTrueAfterReadingFinalItem(): void
    {
        $proxy = new JsonStreamProxy(
            generator: static function (): \Generator {
                yield [
                    'a' => 1,
                ];
            },
        );

        $proxy->read();

        self::assertTrue($proxy->eof());
    }

    public function testEofTrueOnEmptyGeneratorAfterRead(): void
    {
        $proxy = new JsonStreamProxy(
            generator: static function (): \Generator {
                yield from [];
            },
        );

        $proxy->read();

        self::assertTrue($proxy->eof());
    }

    public function testReadJsonlEncodesEachValueWithTrailingNewline(): void
    {
        $proxy = new JsonStreamProxy(
            generator: static function (): \Generator {
                yield [
                    'a' => 1,
                ];

                yield [
                    'b' => 2,
                ];
            },
        );

        self::assertSame("{\"a\":1}\n", $proxy->read());
        self::assertSame("{\"b\":2}\n", $proxy->read());
    }

    public function testReadJsonlEncodesScalarValues(): void
    {
        $proxy = new JsonStreamProxy(
            generator: static function (): \Generator {
                yield 'hello';

                yield 42;

                yield true;
            },
        );

        self::assertSame("\"hello\"\n", $proxy->read());
        self::assertSame("42\n", $proxy->read());
        self::assertSame("true\n", $proxy->read());
    }

    public function testReadRfc7464PrefixesEachValueWithRecordSeparator(): void
    {
        $proxy = new JsonStreamProxy(
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

        self::assertSame("\x1e{\"a\":1}\n", $proxy->read());
        self::assertSame("\x1e{\"b\":2}\n", $proxy->read());
    }

    public function testReadReturnsNullAfterExhausted(): void
    {
        $proxy = new JsonStreamProxy(
            generator: static function (): \Generator {
                yield [
                    'a' => 1,
                ];
            },
        );

        $proxy->read();

        self::assertNull($proxy->read());
    }

    public function testReadReturnsNullOnEmptyGenerator(): void
    {
        $proxy = new JsonStreamProxy(
            generator: static function (): \Generator {
                yield from [];
            },
        );

        self::assertNull($proxy->read());
    }

    public function testReadThrowsJsonExceptionOnUnencodableValue(): void
    {
        $proxy = new JsonStreamProxy(
            generator: static function (): \Generator {
                yield NAN;
            },
        );

        self::expectException(\JsonException::class);

        $proxy->read();
    }

    public function testContentsReturnsConcatenatedEncodedItems(): void
    {
        $proxy = new JsonStreamProxy(
            generator: static function (): \Generator {
                yield [
                    'a' => 1,
                ];

                yield [
                    'b' => 2,
                ];
            },
        );

        self::assertSame("{\"a\":1}\n{\"b\":2}\n", $proxy->contents());
    }

    public function testContentsForRfc7464ConcatenatesRecordSeparatedItems(): void
    {
        $proxy = new JsonStreamProxy(
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

        self::assertSame("\x1e{\"a\":1}\n\x1e{\"b\":2}\n", $proxy->contents());
    }

    public function testContentsOnEmptyGeneratorReturnsEmptyString(): void
    {
        $proxy = new JsonStreamProxy(
            generator: static function (): \Generator {
                yield from [];
            },
        );

        self::assertSame('', $proxy->contents());
    }
}
