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

namespace Unit\View\Lumi\Library\Standard\Filter;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\View\Lumi\Library\Standard\Filter\CollectionFilters;

class CollectionFiltersTest extends TestCase
{
    private CollectionFilters $filters;

    protected function setUp(): void
    {
        $this->filters = new CollectionFilters();
    }

    public function testCountCountsElements(): void
    {
        self::assertSame(
            3,
            $this->filters->count(
                [
                    'a',
                    'b',
                    'c',
                ],
            ),
        );
    }

    public function testCountReturnsZeroForEmptyArray(): void
    {
        self::assertSame(0, $this->filters->count([]));
    }

    public function testLengthReturnsStringLength(): void
    {
        self::assertSame(5, $this->filters->length('hello'));
    }

    public function testLengthReturnsArrayCount(): void
    {
        self::assertSame(
            3,
            $this->filters->length(
                [
                    'a',
                    'b',
                    'c',
                ],
            ),
        );
    }

    public function testLengthReturnsCountableCount(): void
    {
        $countable = new \ArrayObject(
            [
                1,
                2,
                3,
                4,
            ],
        );

        self::assertSame(4, $this->filters->length($countable));
    }

    public function testLengthHandlesMbString(): void
    {
        self::assertSame(5, $this->filters->length('héllo'));
    }

    public function testLengthCountsGraphemes(): void
    {
        self::assertSame(1, $this->filters->length("\u{1F44D}\u{1F3FD}"));
        self::assertSame(1, $this->filters->length("A\u{030A}"));
        self::assertSame(1, $this->filters->length("\u{1F468}\u{200D}\u{1F469}\u{200D}\u{1F467}"));
    }

    public function testLengthFallsBackToCodePointsForInvalidUtf8(): void
    {
        self::assertSame(3, $this->filters->length("\xFFab"));
    }

    /**
     * @return \Generator<string, array{0: iterable<mixed>|string, 1: mixed, 2: mixed}>
     */
    public static function firstLastDataProvider(): \Generator
    {
        yield 'list' => [
            [
                'a',
                'b',
                'c',
            ],
            'a',
            'c',
        ];

        yield 'keyed array' => [
            [
                'x' => 1,
                'y' => 2,
            ],
            1,
            2,
        ];

        yield 'empty array' => [
            [],
            null,
            null,
        ];

        yield 'string by grapheme' => [
            "A\u{030A}se",
            "A\u{030A}",
            'e',
        ];

        yield 'invalid UTF-8 string' => [
            "\xFFab",
            "\xFF",
            'b',
        ];

        yield 'empty string' => [
            '',
            null,
            null,
        ];

        yield 'traversable' => [
            new \ArrayIterator(
                [
                    'p' => 'first',
                    'q' => 'last',
                ],
            ),
            'first',
            'last',
        ];

        yield 'empty traversable' => [
            new \ArrayIterator([]),
            null,
            null,
        ];
    }

    /**
     * @param iterable<mixed>|string $input
     */
    #[DataProvider('firstLastDataProvider')]
    public function testFirstAndLast(
        iterable|string $input,
        mixed $first,
        mixed $last,
    ): void {
        self::assertSame($first, $this->filters->first($input));
        self::assertSame($last, $this->filters->last($input));
    }

    public function testFirstOnlyReadsTheFirstItemOfAGenerator(): void
    {
        $generator = (static function (): \Generator {
            yield 'a';

            self::fail('Read past the first item');
        })();

        self::assertSame('a', $this->filters->first($generator));
    }

    /**
     * @return \Generator<string, array{0: iterable<mixed>|string, 1: mixed, 2: mixed}>
     */
    public static function firstLastKeyDataProvider(): \Generator
    {
        yield 'list' => [
            [
                'a',
                'b',
                'c',
            ],
            0,
            2,
        ];

        yield 'keyed array' => [
            [
                'x' => 1,
                'y' => 2,
            ],
            'x',
            'y',
        ];

        yield 'empty array' => [
            [],
            null,
            null,
        ];

        yield 'string by grapheme' => [
            "A\u{030A}se",
            0,
            2,
        ];

        yield 'empty string' => [
            '',
            null,
            null,
        ];

        yield 'traversable' => [
            new \ArrayIterator(
                [
                    'p' => 'first',
                    'q' => 'last',
                ],
            ),
            'p',
            'q',
        ];

        yield 'empty traversable' => [
            new \ArrayIterator([]),
            null,
            null,
        ];
    }

    /**
     * @param iterable<mixed>|string $input
     */
    #[DataProvider('firstLastKeyDataProvider')]
    public function testFirstKeyAndLastKey(
        iterable|string $input,
        mixed $firstKey,
        mixed $lastKey,
    ): void {
        self::assertSame($firstKey, $this->filters->firstKey($input));
        self::assertSame($lastKey, $this->filters->lastKey($input));
    }

    public function testFirstKeyOnlyReadsTheFirstItemOfAGenerator(): void
    {
        $generator = (static function (): \Generator {
            yield 'k' => 'a';

            self::fail('Read past the first item');
        })();

        self::assertSame('k', $this->filters->firstKey($generator));
    }
}
