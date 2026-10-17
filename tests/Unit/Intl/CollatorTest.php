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

use PHPUnit\Framework\TestCase;
use Tuxxedo\Intl\Collator;
use Tuxxedo\Intl\Locale;

class CollatorTest extends TestCase
{
    public function testRootCollationIgnoresCaseAndKeepsAccentsNextToTheirBaseLetter(): void
    {
        $collator = new Collator();
        $neutral = Locale::parse('und');

        self::assertLessThan(0, $collator->compare('apple', 'Banana', $neutral));
        self::assertLessThan(0, $collator->compare('Catalan', 'Čeština', $neutral));
        self::assertLessThan(0, $collator->compare('Čeština', 'Dansk', $neutral));
        self::assertGreaterThan(0, $collator->compare('Dansk', 'Čeština', $neutral));
        self::assertSame(0, $collator->compare('Dansk', 'Dansk', $neutral));
    }

    public function testLocaleSpecificCollationDiffersFromRoot(): void
    {
        $collator = new Collator();
        $swedish = Locale::parse('sv-SE');

        self::assertLessThan(0, $collator->compare('ö', 'z', Locale::parse('und')));
        self::assertGreaterThan(0, $collator->compare('ö', 'z', $swedish));
        self::assertGreaterThan(0, $collator->compare('ö', 'z', $swedish));
    }

    public function testSortOrdersStringsByLocaleAndKeepsKeys(): void
    {
        $collator = new Collator();
        $names = [
            'Zoë',
            'Åse',
            'anna',
            'Bo',
            'Émile',
        ];

        self::assertSame(
            [
                2 => 'anna',
                1 => 'Åse',
                3 => 'Bo',
                4 => 'Émile',
                0 => 'Zoë',
            ],
            $collator->sort($names, Locale::parse('en-US')),
        );

        self::assertSame(
            [
                2 => 'anna',
                3 => 'Bo',
                4 => 'Émile',
                0 => 'Zoë',
                1 => 'Åse',
            ],
            $collator->sort($names, Locale::parse('da-DK')),
        );
    }

    public function testSortComparesNonStringValuesNumerically(): void
    {
        self::assertSame(
            [
                1 => 1,
                2 => 2,
                0 => 10,
            ],
            (new Collator())->sort(
                [
                    10,
                    1,
                    2,
                ],
                Locale::parse('und'),
            ),
        );
    }

    public function testSortKeysOrdersStringKeysByLocale(): void
    {
        self::assertSame(
            [
                'anna' => 3,
                'émile' => 1,
                'Zoë' => 2,
            ],
            (new Collator())->sortKeys(
                [
                    'émile' => 1,
                    'Zoë' => 2,
                    'anna' => 3,
                ],
                Locale::parse('en-US'),
            ),
        );
    }

    public function testSortKeysComparesIntegerKeysNumerically(): void
    {
        self::assertSame(
            [
                2 => 'b',
                10 => 'a',
            ],
            (new Collator())->sortKeys(
                [
                    10 => 'a',
                    2 => 'b',
                ],
                Locale::parse('und'),
            ),
        );
    }
}
