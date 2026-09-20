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

namespace Unit\Model\Attribute\Aggregate;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Model\Attribute\Aggregate\RelationAggregateFunction;

class RelationAggregateFunctionTest extends TestCase
{
    /**
     * @return \Generator<string, array{0: RelationAggregateFunction, 1: bool}>
     */
    public static function providesRequiresColumn(): \Generator
    {
        yield 'count no column' => [RelationAggregateFunction::COUNT, false];
        yield 'sum needs column' => [RelationAggregateFunction::SUM, true];
        yield 'avg needs column' => [RelationAggregateFunction::AVG, true];
        yield 'min needs column' => [RelationAggregateFunction::MIN, true];
        yield 'max needs column' => [RelationAggregateFunction::MAX, true];
    }

    #[DataProvider('providesRequiresColumn')]
    public function testRequiresColumn(
        RelationAggregateFunction $function,
        bool $expected,
    ): void {
        self::assertSame($expected, $function->requiresColumn());
    }

    /**
     * @return \Generator<string, array{0: RelationAggregateFunction, 1: string}>
     */
    public static function providesSqlFunction(): \Generator
    {
        yield 'count' => [RelationAggregateFunction::COUNT, 'COUNT'];
        yield 'sum' => [RelationAggregateFunction::SUM, 'SUM'];
        yield 'avg' => [RelationAggregateFunction::AVG, 'AVG'];
        yield 'min' => [RelationAggregateFunction::MIN, 'MIN'];
        yield 'max' => [RelationAggregateFunction::MAX, 'MAX'];
    }

    #[DataProvider('providesSqlFunction')]
    public function testSqlFunction(
        RelationAggregateFunction $function,
        string $expected,
    ): void {
        self::assertSame($expected, $function->sqlFunction());
    }

    /**
     * @return \Generator<string, array{0: RelationAggregateFunction, 1: ?string, 2: string}>
     */
    public static function providesDefaultAliasSuffix(): \Generator
    {
        yield 'count ignores column' => [RelationAggregateFunction::COUNT, null, 'count'];
        yield 'sum with column' => [RelationAggregateFunction::SUM, 'amount', 'sum_amount'];
        yield 'avg with column' => [RelationAggregateFunction::AVG, 'amount', 'avg_amount'];
        yield 'min with column' => [RelationAggregateFunction::MIN, 'amount', 'min_amount'];
        yield 'max with column' => [RelationAggregateFunction::MAX, 'amount', 'max_amount'];
    }

    #[DataProvider('providesDefaultAliasSuffix')]
    public function testDefaultAliasSuffix(
        RelationAggregateFunction $function,
        ?string $column,
        string $expected,
    ): void {
        self::assertSame(
            $expected,
            $function->defaultAliasSuffix(column: $column),
        );
    }
}
