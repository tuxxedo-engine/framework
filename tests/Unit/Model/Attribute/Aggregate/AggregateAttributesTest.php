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

use PHPUnit\Framework\TestCase;
use Tuxxedo\Database\Query\Statement\Aggregate\AggregateFunction;
use Tuxxedo\Model\Attribute\Aggregate\AvgAggregate;
use Tuxxedo\Model\Attribute\Aggregate\CountAggregate;
use Tuxxedo\Model\Attribute\Aggregate\MaxAggregate;
use Tuxxedo\Model\Attribute\Aggregate\MinAggregate;
use Tuxxedo\Model\Attribute\Aggregate\RelationAggregate;
use Tuxxedo\Model\Attribute\Aggregate\SumAggregate;

class AggregateAttributesTest extends TestCase
{
    public function testBaseAggregateHoldsAllFields(): void
    {
        $attribute = new RelationAggregate(
            relation: 'tags',
            function: AggregateFunction::COUNT,
            column: null,
            alias: 'my_count',
        );

        self::assertSame('tags', $attribute->relation);
        self::assertSame(AggregateFunction::COUNT, $attribute->function);
        self::assertNull($attribute->column);
        self::assertSame('my_count', $attribute->alias);
    }

    public function testCountAggregateHardcodesCount(): void
    {
        $attribute = new CountAggregate(relation: 'tags');

        self::assertInstanceOf(RelationAggregate::class, $attribute);
        self::assertSame(AggregateFunction::COUNT, $attribute->function);
        self::assertSame('tags', $attribute->relation);
        self::assertNull($attribute->column);
        self::assertNull($attribute->alias);
    }

    public function testSumAggregateHardcodesSum(): void
    {
        $attribute = new SumAggregate(
            relation: 'orders',
            column: 'amount',
            alias: 'total',
        );

        self::assertInstanceOf(RelationAggregate::class, $attribute);
        self::assertSame(AggregateFunction::SUM, $attribute->function);
        self::assertSame('orders', $attribute->relation);
        self::assertSame('amount', $attribute->column);
        self::assertSame('total', $attribute->alias);
    }

    public function testAvgAggregateHardcodesAvg(): void
    {
        $attribute = new AvgAggregate(
            relation: 'orders',
            column: 'amount',
        );

        self::assertSame(AggregateFunction::AVG, $attribute->function);
        self::assertSame('amount', $attribute->column);
    }

    public function testMinAggregateHardcodesMin(): void
    {
        $attribute = new MinAggregate(
            relation: 'notes',
            column: 'length',
        );

        self::assertSame(AggregateFunction::MIN, $attribute->function);
        self::assertSame('length', $attribute->column);
    }

    public function testMaxAggregateHardcodesMax(): void
    {
        $attribute = new MaxAggregate(
            relation: 'notes',
            column: 'length',
        );

        self::assertSame(AggregateFunction::MAX, $attribute->function);
        self::assertSame('length', $attribute->column);
    }
}
