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

use Fixture\Model\Post;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Model\Relation;

class RelationTest extends TestCase
{
    public function testAddCancelsMatchingPendingRemove(): void
    {
        $item = new Post();

        $relation = Relation::createFromPrefetched(
            values: [
                $item,
            ],
        );

        $relation->remove(item: $item);

        self::assertContains(
            $item,
            $relation->pendingRemoves,
        );

        $relation->add(item: $item);

        self::assertNotContains(
            $item,
            $relation->pendingRemoves,
        );

        self::assertNotContains(
            $item,
            $relation->pendingAdds,
        );
    }

    public function testFirstReturnsNullWhenPendingRemovesEmptyMaterializedResult(): void
    {
        $alpha = new Post();
        $beta = new Post();

        $relation = Relation::createFromPrefetched(
            values: [
                $alpha,
                $beta,
            ],
        );

        $relation->remove(item: $alpha);
        $relation->remove(item: $beta);

        self::assertNull(
            $relation->first(),
        );
    }

    public function testTotalCountReturnsPrefetchedSize(): void
    {
        $relation = Relation::createFromPrefetched(
            values: [
                new Post(),
                new Post(),
                new Post(),
            ],
        );

        self::assertSame(
            3,
            $relation->totalCount,
        );
    }
}
