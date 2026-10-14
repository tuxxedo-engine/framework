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
use Tuxxedo\Intl\CldrCategory;
use Tuxxedo\Intl\Locale;
use Tuxxedo\Intl\PluralSelector;

class PluralSelectorTest extends TestCase
{
    public function testSelectsPerLocaleWhenLocalesAreInterleaved(): void
    {
        $selector = new PluralSelector();
        $english = Locale::parse('en-US');
        $russian = Locale::parse('ru-RU');
        $arabic = Locale::parse('ar');

        self::assertSame(CldrCategory::ONE, $selector->select($english, 1));
        self::assertSame(CldrCategory::FEW, $selector->select($russian, 2));
        self::assertSame(CldrCategory::OTHER, $selector->select($english, 2));
        self::assertSame(CldrCategory::MANY, $selector->select($russian, 5));
        self::assertSame(CldrCategory::ZERO, $selector->select($arabic, 0));
        self::assertSame(CldrCategory::TWO, $selector->select($arabic, 2));
        self::assertSame(CldrCategory::OTHER, $selector->select($english, 0));
        self::assertSame(CldrCategory::ONE, $selector->select($russian, 21));
    }
}
