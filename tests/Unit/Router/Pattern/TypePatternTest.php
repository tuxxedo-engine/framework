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

namespace Unit\Router\Pattern;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Router\Pattern\TypePattern;

class TypePatternTest extends TestCase
{
    public function testConstructorExposesName(): void
    {
        $pattern = new TypePattern(
            name: 'test',
            regex: '[a-z]+',
        );

        self::assertSame('test', $pattern->name);
    }

    public function testConstructorExposesRegex(): void
    {
        $pattern = new TypePattern(
            name: 'test',
            regex: '[a-z]+',
        );

        self::assertSame('[a-z]+', $pattern->regex);
    }
}
