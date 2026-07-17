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
use Tuxxedo\Model\Query;
use Tuxxedo\Model\Relation;

class QueryTest extends TestCase
{
    public function testWithReplacesNullBaselineWithProvidedMap(): void
    {
        $query = Query::createFromBuilder(
            loaderBuilder: static fn (array $criteria, array $orderBy, ?int $limit, ?int $offset): iterable => [],
            countBuilder: static fn (array $criteria): int => 0,
        );

        $extended = $query->with(
            with: [
                'profile' => static fn (Relation $relation): Relation => $relation,
            ],
        );

        self::assertNotSame(
            $query,
            $extended,
        );
    }
}
