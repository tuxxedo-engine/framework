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

namespace Unit\Pagination;

use Fixture\Pagination\ArrayPaged;
use Fixture\Pagination\TestItem;
use PHPUnit\Framework\TestCase;

class ArrayPagedTest extends TestCase
{
    public function testTotalCountIsZeroForEmptySource(): void
    {
        $paged = new ArrayPaged(
            items: [],
        );

        self::assertSame(0, $paged->totalCount);
    }

    public function testTotalCountReflectsNumberOfItems(): void
    {
        $paged = new ArrayPaged(
            items: [
                new TestItem(id: 1),
                new TestItem(id: 2),
                new TestItem(id: 3),
            ],
        );

        self::assertSame(3, $paged->totalCount);
    }

    public function testSliceFromStartReturnsFirstWindow(): void
    {
        $paged = new ArrayPaged(
            items: [
                new TestItem(id: 1),
                new TestItem(id: 2),
                new TestItem(id: 3),
                new TestItem(id: 4),
            ],
        );

        $result = $paged->slice(
            limit: 2,
            offset: 0,
        );

        self::assertCount(2, $result);
        self::assertSame(1, $result[0]->id);
        self::assertSame(2, $result[1]->id);
    }

    public function testSliceWithOffsetSkipsLeadingItems(): void
    {
        $paged = new ArrayPaged(
            items: [
                new TestItem(id: 1),
                new TestItem(id: 2),
                new TestItem(id: 3),
                new TestItem(id: 4),
            ],
        );

        $result = $paged->slice(
            limit: 2,
            offset: 2,
        );

        self::assertCount(2, $result);
        self::assertSame(3, $result[0]->id);
        self::assertSame(4, $result[1]->id);
    }

    public function testSliceWithLimitExceedingRemainingClampsToAvailable(): void
    {
        $paged = new ArrayPaged(
            items: [
                new TestItem(id: 1),
                new TestItem(id: 2),
                new TestItem(id: 3),
            ],
        );

        $result = $paged->slice(
            limit: 10,
            offset: 1,
        );

        self::assertCount(2, $result);
        self::assertSame(2, $result[0]->id);
        self::assertSame(3, $result[1]->id);
    }

    public function testSliceWithOffsetPastEndReturnsEmpty(): void
    {
        $paged = new ArrayPaged(
            items: [
                new TestItem(id: 1),
                new TestItem(id: 2),
            ],
        );

        $result = $paged->slice(
            limit: 5,
            offset: 10,
        );

        self::assertCount(0, $result);
    }

    public function testSliceOnEmptySourceReturnsEmpty(): void
    {
        $paged = new ArrayPaged(
            items: [],
        );

        $result = $paged->slice(
            limit: 5,
            offset: 0,
        );

        self::assertCount(0, $result);
    }

    public function testSliceResultIsAList(): void
    {
        $paged = new ArrayPaged(
            items: [
                new TestItem(id: 1),
                new TestItem(id: 2),
                new TestItem(id: 3),
            ],
        );

        $result = $paged->slice(
            limit: 2,
            offset: 1,
        );

        self::assertSame(
            [0, 1],
            \array_keys($result),
        );
    }
}
