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

namespace Unit\Model\Attribute;

use Fixture\Model\CustomTinyColumn;
use PHPUnit\Framework\TestCase;

class ColumnTest extends TestCase
{
    public function testCustomColumnSubclassDelegatesToBaseConstructor(): void
    {
        $column = new CustomTinyColumn(
            precision: 6,
            name: 'external_column',
        );

        self::assertSame(
            6,
            $column->precision,
        );

        self::assertSame(
            'external_column',
            $column->name,
        );

        self::assertNull(
            $column->coercer,
        );

        self::assertNull(
            $column->behavior,
        );

        self::assertSame(
            [],
            $column->coercerArguments,
        );
    }
}
