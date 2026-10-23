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

namespace Unit\View\Lumi\Library\Standard;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\View\Lumi\Library\Standard\CollectionLibrary;

class CollectionLibraryTest extends TestCase
{
    private CollectionLibrary $library;

    protected function setUp(): void
    {
        $this->library = new CollectionLibrary();
    }

    public function testKsortSortsByKey(): void
    {
        self::assertSame(
            [
                'a' => 2,
                'b' => 3,
                'c' => 1,
            ],
            $this->library->ksort(
                [
                    'b' => 3,
                    'c' => 1,
                    'a' => 2,
                ],
            ),
        );
    }

    public function testSortSortsByValuePreservingKeys(): void
    {
        self::assertSame(
            [
                'a' => 1,
                'b' => 2,
                'c' => 3,
            ],
            $this->library->sort(
                [
                    'c' => 3,
                    'a' => 1,
                    'b' => 2,
                ],
            ),
        );
    }

    public function testReverseReversesString(): void
    {
        self::assertSame('olleh', $this->library->reverse('hello'));
    }

    public function testReverseKeepsGraphemeClustersWhole(): void
    {
        self::assertSame("esA\u{030A}", $this->library->reverse("A\u{030A}se"));
        self::assertSame("ko\u{1F44D}\u{1F3FD}", $this->library->reverse("\u{1F44D}\u{1F3FD}ok"));
        self::assertSame('esÅ', $this->library->reverse('Åse'));
    }

    public function testReverseFallsBackToCodePointsForInvalidUtf8(): void
    {
        self::assertSame("\xFFba", $this->library->reverse("ab\xFF"));
    }

    public function testSliceReindexesListsAndKeepsStringKeys(): void
    {
        self::assertSame(
            [
                'b',
                'c',
            ],
            $this->library->slice(
                [
                    'a',
                    'b',
                    'c',
                    'd',
                ],
                1,
                2,
            ),
        );

        self::assertSame(
            [
                'y' => 2,
                'z' => 3,
            ],
            $this->library->slice(
                [
                    'x' => 1,
                    'y' => 2,
                    'z' => 3,
                ],
                -2,
            ),
        );
    }

    public function testSliceCutsStringsByGrapheme(): void
    {
        self::assertSame("A\u{030A}se", $this->library->slice("A\u{030A}se Øst", 0, 3));
        self::assertSame('Øst', $this->library->slice('Åse Øst', -3));
    }

    public function testReverseReversesArrayAndKeepsKeys(): void
    {
        self::assertSame(
            [
                2 => 3,
                1 => 2,
                0 => 1,
            ],
            $this->library->reverse(
                [
                    1,
                    2,
                    3,
                ],
            ),
        );
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
        self::assertSame($first, $this->library->first($input));
        self::assertSame($last, $this->library->last($input));
    }

    public function testFirstOnlyReadsTheFirstItemOfAGenerator(): void
    {
        $generator = (static function (): \Generator {
            yield 'a';

            self::fail('Read past the first item');
        })();

        self::assertSame('a', $this->library->first($generator));
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
        self::assertSame($firstKey, $this->library->firstKey($input));
        self::assertSame($lastKey, $this->library->lastKey($input));
    }

    public function testFirstKeyOnlyReadsTheFirstItemOfAGenerator(): void
    {
        $generator = (static function (): \Generator {
            yield 'k' => 'a';

            self::fail('Read past the first item');
        })();

        self::assertSame('k', $this->library->firstKey($generator));
    }

    public function testLengthReturnsStringLength(): void
    {
        self::assertSame(5, $this->library->length('hello'));
    }

    public function testLengthReturnsArrayCount(): void
    {
        self::assertSame(
            3,
            $this->library->length(
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

        self::assertSame(4, $this->library->length($countable));
    }

    public function testLengthHandlesMbString(): void
    {
        self::assertSame(5, $this->library->length('héllo'));
    }

    public function testLengthCountsGraphemes(): void
    {
        self::assertSame(1, $this->library->length("\u{1F44D}\u{1F3FD}"));
        self::assertSame(1, $this->library->length("A\u{030A}"));
        self::assertSame(1, $this->library->length("\u{1F468}\u{200D}\u{1F469}\u{200D}\u{1F467}"));
    }

    public function testLengthFallsBackToCodePointsForInvalidUtf8(): void
    {
        self::assertSame(3, $this->library->length("\xFFab"));
    }
}
