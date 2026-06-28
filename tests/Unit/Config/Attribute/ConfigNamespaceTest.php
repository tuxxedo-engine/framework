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
use Tuxxedo\Config\Attribute\ConfigNamespace;

class ConfigNamespaceTest extends TestCase
{
    public function testStoresFlatNamespace(): void
    {
        $attribute = new ConfigNamespace(
            namespace: 'database',
        );

        self::assertSame('database', $attribute->namespace);
    }

    public function testStoresDottedNamespace(): void
    {
        $attribute = new ConfigNamespace(
            namespace: 'database.manager',
        );

        self::assertSame('database.manager', $attribute->namespace);
    }
}
