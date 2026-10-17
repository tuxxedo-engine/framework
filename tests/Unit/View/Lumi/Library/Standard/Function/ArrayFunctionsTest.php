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

namespace Unit\View\Lumi\Library\Standard\Function;

use PHPUnit\Framework\TestCase;
use Tuxxedo\View\Lumi\Library\Standard\Function\ArrayFunctions;

class ArrayFunctionsTest extends TestCase
{
    private ArrayFunctions $functions;

    protected function setUp(): void
    {
        $this->functions = new ArrayFunctions();
    }

    public function testKsortSortsByKey(): void
    {
        self::assertSame(
            [
                'a' => 2,
                'b' => 3,
                'c' => 1,
            ],
            $this->functions->ksort(
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
            $this->functions->sort(
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
        self::assertSame('olleh', $this->functions->reverse('hello'));
    }

    public function testReverseKeepsGraphemeClustersWhole(): void
    {
        self::assertSame("esA\u{030A}", $this->functions->reverse("A\u{030A}se"));
        self::assertSame("ko\u{1F44D}\u{1F3FD}", $this->functions->reverse("\u{1F44D}\u{1F3FD}ok"));
        self::assertSame('esÅ', $this->functions->reverse('Åse'));
    }

    public function testReverseFallsBackToCodePointsForInvalidUtf8(): void
    {
        self::assertSame("\xFFba", $this->functions->reverse("ab\xFF"));
    }

    public function testReverseReversesArrayAndKeepsKeys(): void
    {
        self::assertSame(
            [
                2 => 3,
                1 => 2,
                0 => 1,
            ],
            $this->functions->reverse(
                [
                    1,
                    2,
                    3,
                ],
            ),
        );
    }
}
