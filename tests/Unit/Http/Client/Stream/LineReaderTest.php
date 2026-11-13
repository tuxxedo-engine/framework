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

namespace Unit\Http\Client\Stream;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Http\Client\Stream\LineReader;
use Tuxxedo\Http\Stream\Stream;

class LineReaderTest extends TestCase
{
    /**
     * @return \Generator<array{0: list<string>, 1: list<string>}>
     */
    public static function linesDataProvider(): \Generator
    {
        yield [
            [
                "a\nb\r\nc\rd",
            ],
            [
                'a',
                'b',
                'c',
                'd',
            ],
        ];

        yield [
            [
                "a\r",
                "\nb\n",
            ],
            [
                'a',
                'b',
            ],
        ];

        yield [
            [
                'ab',
                "c\n\nd\r",
            ],
            [
                'abc',
                '',
                'd',
            ],
        ];

        yield [
            [],
            [],
        ];
    }

    /**
     * @param list<string> $chunks
     * @param list<string> $expected
     */
    #[DataProvider('linesDataProvider')]
    public function testSplitsLinesAcrossChunks(
        array $chunks,
        array $expected,
    ): void {
        self::assertSame(
            $expected,
            \iterator_to_array(
                LineReader::read(
                    Stream::fromGenerator(
                        static function () use ($chunks): \Generator {
                            yield from $chunks;
                        },
                    ),
                ),
                false,
            ),
        );
    }
}
