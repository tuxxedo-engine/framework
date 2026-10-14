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

namespace Unit\Intl\Resolver;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Intl\Config\IntlConfig;
use Tuxxedo\Intl\Locale;
use Tuxxedo\Intl\Resolver\StaticLocaleResolver;

class StaticLocaleResolverTest extends TestCase
{
    public function testReturnsConfiguredDefaultLocale(): void
    {
        $resolver = new StaticLocaleResolver(
            config: new IntlConfig(
                rootDirectory: '/tmp/language',
                defaultLocale: Locale::parse('da-DK'),
            ),
        );

        self::assertSame('da-DK', $resolver->currentLocale()->tag);
    }
}
