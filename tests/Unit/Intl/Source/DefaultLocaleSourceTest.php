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

namespace Unit\Intl\Source;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Intl\Config\IntlConfig;
use Tuxxedo\Intl\Locale;
use Tuxxedo\Intl\Source\DefaultLocaleSource;

class DefaultLocaleSourceTest extends TestCase
{
    public function testReturnsConfiguredDefaultLocale(): void
    {
        $source = new DefaultLocaleSource(
            config: new IntlConfig(
                rootDirectory: '/unused',
                defaultLocale: Locale::parse('en-US'),
            ),
        );

        $detected = $source->detect();

        self::assertNotNull($detected);
        self::assertSame('en-US', $detected->tag);
    }
}
