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

namespace Unit\Model;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Model\ModelException;
use Tuxxedo\Model\Relation;

class AbstractQueryableAggregateGuardTest extends TestCase
{
    public function testWithCountRequiresModelContext(): void
    {
        $relation = Relation::createFromBuilder(
            loaderBuilder: static fn (array $criteria, array $orderBy, ?int $limit, ?int $offset): iterable => [],
            countBuilder: static fn (array $criteria): int => 0,
        );

        try {
            (void) $relation->withCount(relationName: 'tags');

            self::fail('Expected ModelException was not thrown');
        } catch (ModelException $exception) {
            self::assertStringContainsString(
                'withCount',
                $exception->getMessage(),
            );
        }
    }

    public function testWithSumRequiresModelContext(): void
    {
        $relation = Relation::createFromBuilder(
            loaderBuilder: static fn (array $criteria, array $orderBy, ?int $limit, ?int $offset): iterable => [],
            countBuilder: static fn (array $criteria): int => 0,
        );

        try {
            (void) $relation->withSum(relationName: 'orders', column: 'amount');

            self::fail('Expected ModelException was not thrown');
        } catch (ModelException $exception) {
            self::assertStringContainsString(
                'withSum',
                $exception->getMessage(),
            );
        }
    }
}
