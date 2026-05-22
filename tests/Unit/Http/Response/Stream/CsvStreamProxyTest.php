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
use Tuxxedo\Http\Response\Stream\CsvStreamProxy;
use Tuxxedo\Http\Response\Stream\StreamProxyInterface;

class CsvStreamProxyTest extends TestCase
{
    /**
     * @param \Closure(): \Generator<scalar[]>|\Generator<scalar[]> $generator
     * @param string[]|null $columns
     */
    private static function makeProxy(
        \Closure|\Generator $generator,
        string $separator = ',',
        string $enclosure = '"',
        string $eol = "\n",
        ?array $columns = null,
    ): StreamProxyInterface {
        return new CsvStreamProxy(
            generator: $generator,
            separator: $separator,
            enclosure: $enclosure,
            eol: $eol,
            columns: $columns,
        );
    }

    public function testConstructorWithClosure(): void
    {
        $proxy = new CsvStreamProxy(
            generator: static function (): \Generator {
                yield [
                    'a',
                    'b',
                ];
            },
        );

        self::assertSame("a,b\n", $proxy->read());
    }

    public function testConstructorWithGenerator(): void
    {
        $generator = (static function (): \Generator {
            yield [
                'a',
                'b',
            ];
        })();

        $proxy = new CsvStreamProxy(
            generator: $generator,
        );

        self::assertSame("a,b\n", $proxy->read());
    }

    public function testImplementsPrefersHeadersInterface(): void
    {
        $proxy = new CsvStreamProxy(
            generator: static function (): \Generator {
                yield from [];
            },
        );

        self::assertInstanceOf(PrefersHeadersInterface::class, $proxy);
    }

    public function testHeadersExposeCsvContentType(): void
    {
        $proxy = new CsvStreamProxy(
            generator: static function (): \Generator {
                yield from [];
            },
        );

        self::assertCount(1, $proxy->headers);
        self::assertSame('Content-Type', $proxy->headers[0]->name);
        self::assertSame('text/csv; charset=utf-8', $proxy->headers[0]->value);
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

    public function testEofFalseInitiallyWhenGeneratorHasRows(): void
    {
        $proxy = new CsvStreamProxy(
            generator: static function (): \Generator {
                yield [
                    'a',
                ];
            },
        );

        self::assertFalse($proxy->eof());
    }

    public function testEofTrueAfterReadingFinalRow(): void
    {
        $proxy = new CsvStreamProxy(
            generator: static function (): \Generator {
                yield [
                    'a',
                ];
            },
        );

        $proxy->read();

        self::assertTrue($proxy->eof());
    }

    public function testReadReturnsRowsInOrder(): void
    {
        $proxy = new CsvStreamProxy(
            generator: static function (): \Generator {
                yield [
                    'foo',
                    'bar',
                ];

                yield [
                    'baz',
                    'qux',
                ];
            },
        );

        self::assertSame("foo,bar\n", $proxy->read());
        self::assertSame("baz,qux\n", $proxy->read());
    }

    public function testReadReturnsNullAfterExhausted(): void
    {
        $proxy = new CsvStreamProxy(
            generator: static function (): \Generator {
                yield [
                    'a',
                ];
            },
        );

        $proxy->read();

        self::assertNull($proxy->read());
    }

    public function testReadReturnsNullOnEmptyGenerator(): void
    {
        $proxy = new CsvStreamProxy(
            generator: static function (): \Generator {
                yield from [];
            },
        );

        self::assertNull($proxy->read());
    }

    public function testContentsReturnsConcatenatedRows(): void
    {
        $proxy = new CsvStreamProxy(
            generator: static function (): \Generator {
                yield [
                    'a',
                    'b',
                ];

                yield [
                    'c',
                    'd',
                ];
            },
        );

        self::assertSame("a,b\nc,d\n", $proxy->contents());
    }

    public function testContentsOnEmptyGeneratorReturnsEmptyString(): void
    {
        $proxy = new CsvStreamProxy(
            generator: static function (): \Generator {
                yield from [];
            },
        );

        self::assertSame('', $proxy->contents());
    }

    public function testReadEmitsColumnsRowBeforeData(): void
    {
        $proxy = new CsvStreamProxy(
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

        self::assertSame("col-a,col-b\n", $proxy->read());
        self::assertSame("value-a,value-b\n", $proxy->read());
    }

    public function testReadEmitsColumnsRowEvenWhenGeneratorIsEmpty(): void
    {
        $proxy = new CsvStreamProxy(
            generator: static function (): \Generator {
                yield from [];
            },
            columns: [
                'col-a',
                'col-b',
            ],
        );

        self::assertSame("col-a,col-b\n", $proxy->read());
        self::assertNull($proxy->read());
    }

    public function testReadHonorsCustomSeparator(): void
    {
        $proxy = new CsvStreamProxy(
            generator: static function (): \Generator {
                yield [
                    'a',
                    'b',
                    'c',
                ];
            },
            separator: ';',
        );

        self::assertSame("a;b;c\n", $proxy->read());
    }

    public function testReadHonorsCustomEol(): void
    {
        $proxy = new CsvStreamProxy(
            generator: static function (): \Generator {
                yield [
                    'a',
                    'b',
                ];
            },
            eol: "\r\n",
        );

        self::assertSame("a,b\r\n", $proxy->read());
    }

    public function testReadQuotesFieldContainingSeparator(): void
    {
        $proxy = new CsvStreamProxy(
            generator: static function (): \Generator {
                yield [
                    'foo,bar',
                    'baz',
                ];
            },
        );

        self::assertSame("\"foo,bar\",baz\n", $proxy->read());
    }

    public function testReadQuotesFieldContainingEnclosureAndDoublesEnclosure(): void
    {
        $proxy = new CsvStreamProxy(
            generator: static function (): \Generator {
                yield [
                    'he said "hi"',
                ];
            },
        );

        self::assertSame("\"he said \"\"hi\"\"\"\n", $proxy->read());
    }

    public function testReadQuotesFieldContainingNewline(): void
    {
        $proxy = new CsvStreamProxy(
            generator: static function (): \Generator {
                yield [
                    "line1\nline2",
                ];
            },
        );

        self::assertSame("\"line1\nline2\"\n", $proxy->read());
    }

    public function testReadQuotesFieldContainingCarriageReturn(): void
    {
        $proxy = new CsvStreamProxy(
            generator: static function (): \Generator {
                yield [
                    "before\rafter",
                ];
            },
        );

        self::assertSame("\"before\rafter\"\n", $proxy->read());
    }

    public function testReadHonorsCustomEnclosure(): void
    {
        $proxy = new CsvStreamProxy(
            generator: static function (): \Generator {
                yield [
                    'foo,bar',
                ];
            },
            enclosure: '\'',
        );

        self::assertSame("'foo,bar'\n", $proxy->read());
    }

    public function testReadStringifiesScalarFields(): void
    {
        $proxy = new CsvStreamProxy(
            generator: static function (): \Generator {
                yield [
                    1,
                    2.5,
                    true,
                    false,
                ];
            },
        );

        self::assertSame("1,2.5,1,\n", $proxy->read());
    }
}
