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

namespace Unit\Config\Attribute;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Config\Attribute\ConfigKey;
use Tuxxedo\Config\ConfigException;

class ConfigKeyTest extends TestCase
{
    public function testAcceptsPlainLeafName(): void
    {
        $attribute = new ConfigKey(
            name: 'leaf',
        );

        self::assertSame('leaf', $attribute->name);
    }

    public function testRejectsDottedNameWithConfigException(): void
    {
        self::expectException(ConfigException::class);
        self::expectExceptionMessage('Invalid #[ConfigKey] value "nested.path"');

        new ConfigKey(
            name: 'nested.path',
        );
    }
}
