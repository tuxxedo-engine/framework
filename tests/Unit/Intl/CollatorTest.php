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

        self::assertLessThan(0, $collator->compare('apple', 'Banana'));
        self::assertLessThan(0, $collator->compare('Catalan', 'Čeština'));
        self::assertLessThan(0, $collator->compare('Čeština', 'Dansk'));
        self::assertGreaterThan(0, $collator->compare('Dansk', 'Čeština'));
        self::assertSame(0, $collator->compare('Dansk', 'Dansk'));
    }

    public function testLocaleSpecificCollationDiffersFromRoot(): void
    {
        $collator = new Collator();
        $swedish = Locale::parse('sv-SE');

        self::assertLessThan(0, $collator->compare('ö', 'z'));
        self::assertGreaterThan(0, $collator->compare('ö', 'z', $swedish));
        self::assertGreaterThan(0, $collator->compare('ö', 'z', $swedish));
    }
}
