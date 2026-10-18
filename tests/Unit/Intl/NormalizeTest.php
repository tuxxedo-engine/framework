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

namespace Unit\Intl;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Intl\Normalize;

class NormalizeTest extends TestCase
{
    /**
     * @return \Generator<string, array{0: Normalize, 1: string, 2: string|null}>
     */
    public static function providesCases(): \Generator
    {
        yield 'none keeps decomposed text' => [
            Normalize::NONE,
            "Cafe\u{0301}",
            "Cafe\u{0301}",
        ];

        yield 'canonical composes' => [
            Normalize::CANONICAL,
            "Cafe\u{0301}",
            'Café',
        ];

        yield 'canonical keeps compatibility forms' => [
            Normalize::CANONICAL,
            "\u{FB01}le",
            "\u{FB01}le",
        ];

        yield 'compatible folds ligatures' => [
            Normalize::COMPATIBLE,
            "\u{FB01}le",
            'file',
        ];

        yield 'compatible folds full-width' => [
            Normalize::COMPATIBLE,
            "\u{FF41}\u{FF44}\u{FF4D}\u{FF49}\u{FF4E}",
            'admin',
        ];

        yield 'compatible also composes' => [
            Normalize::COMPATIBLE,
            "Cafe\u{0301}",
            'Café',
        ];

        yield 'invalid UTF-8 fails with null' => [
            Normalize::CANONICAL,
            "ab\xFF",
            null,
        ];

        yield 'none never fails' => [
            Normalize::NONE,
            "ab\xFF",
            "ab\xFF",
        ];
    }

    #[DataProvider('providesCases')]
    public function testApply(
        Normalize $normalize,
        string $value,
        ?string $expected,
    ): void {
        self::assertSame($expected, $normalize->apply($value));
    }
}
