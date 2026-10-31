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

namespace Unit\Database\Query\Statement\Aggregate;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Database\Query\Statement\Aggregate\AggregateFunction;

class AggregateFunctionTest extends TestCase
{
    /**
     * @return \Generator<string, array{0: AggregateFunction, 1: bool}>
     */
    public static function providesRequiresColumn(): \Generator
    {
        yield 'count no column' => [
            AggregateFunction::COUNT,
            false,
        ];

        yield 'sum needs column' => [
            AggregateFunction::SUM,
            true,
        ];

        yield 'avg needs column' => [
            AggregateFunction::AVG,
            true,
        ];

        yield 'min needs column' => [
            AggregateFunction::MIN,
            true,
        ];

        yield 'max needs column' => [
            AggregateFunction::MAX,
            true,
        ];
    }

    #[DataProvider('providesRequiresColumn')]
    public function testRequiresColumn(
        AggregateFunction $function,
        bool $expected,
    ): void {
        self::assertSame(
            $expected,
            $function->requiresColumn(),
        );
    }
}
