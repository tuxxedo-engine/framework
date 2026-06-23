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
use Tuxxedo\Pagination\Paginator;

class PaginatorTest extends TestCase
{
    public function testPerPageDefaultsToTwenty(): void
    {
        $paginator = new Paginator(
            paged: new ArrayPaged(
                items: [],
            ),
            page: 1,
        );

        self::assertSame(20, $paginator->perPage);
    }

    public function testConstructorClampsPageBelowOne(): void
    {
        $paginator = new Paginator(
            paged: new ArrayPaged(
                items: [],
            ),
            page: 0,
        );

        self::assertSame(1, $paginator->page);
    }

    public function testConstructorClampsNegativePage(): void
    {
        $paginator = new Paginator(
            paged: new ArrayPaged(
                items: [],
            ),
            page: -5,
        );

        self::assertSame(1, $paginator->page);
    }

    public function testConstructorClampsPerPageBelowOne(): void
    {
        $paginator = new Paginator(
            paged: new ArrayPaged(
                items: [],
            ),
            page: 1,
            perPage: 0,
        );

        self::assertSame(1, $paginator->perPage);
    }

    public function testTotalCountReadsThroughToPaged(): void
    {
        $paginator = new Paginator(
            paged: new ArrayPaged(
                items: $this->makeItems(count: 7),
            ),
            page: 1,
            perPage: 3,
        );

        self::assertSame(7, $paginator->totalCount);
    }

    public function testTotalPagesWithExactFit(): void
    {
        $paginator = new Paginator(
            paged: new ArrayPaged(
                items: $this->makeItems(count: 20),
            ),
            page: 1,
            perPage: 5,
        );

        self::assertSame(4, $paginator->totalPages);
    }

    public function testTotalPagesRoundsUpForRemainder(): void
    {
        $paginator = new Paginator(
            paged: new ArrayPaged(
                items: $this->makeItems(count: 21),
            ),
            page: 1,
            perPage: 5,
        );

        self::assertSame(5, $paginator->totalPages);
    }

    public function testTotalPagesIsZeroForEmptySource(): void
    {
        $paginator = new Paginator(
            paged: new ArrayPaged(
                items: [],
            ),
            page: 1,
            perPage: 10,
        );

        self::assertSame(0, $paginator->totalPages);
    }

    public function testHasNextPageOnFirstPageOfMultiple(): void
    {
        $paginator = new Paginator(
            paged: new ArrayPaged(
                items: $this->makeItems(count: 10),
            ),
            page: 1,
            perPage: 3,
        );

        self::assertTrue($paginator->hasNextPage);
    }

    public function testHasNextPageIsFalseOnLastPage(): void
    {
        $paginator = new Paginator(
            paged: new ArrayPaged(
                items: $this->makeItems(count: 10),
            ),
            page: 4,
            perPage: 3,
        );

        self::assertFalse($paginator->hasNextPage);
    }

    public function testHasPreviousPageIsFalseOnFirstPage(): void
    {
        $paginator = new Paginator(
            paged: new ArrayPaged(
                items: $this->makeItems(count: 10),
            ),
            page: 1,
            perPage: 3,
        );

        self::assertFalse($paginator->hasPreviousPage);
    }

    public function testHasPreviousPageOnSecondPage(): void
    {
        $paginator = new Paginator(
            paged: new ArrayPaged(
                items: $this->makeItems(count: 10),
            ),
            page: 2,
            perPage: 3,
        );

        self::assertTrue($paginator->hasPreviousPage);
    }

    public function testNextPageAdvancesByOne(): void
    {
        $paginator = new Paginator(
            paged: new ArrayPaged(
                items: $this->makeItems(count: 10),
            ),
            page: 2,
            perPage: 3,
        );

        self::assertSame(3, $paginator->nextPage);
    }

    public function testNextPageClampsToLastPage(): void
    {
        $paginator = new Paginator(
            paged: new ArrayPaged(
                items: $this->makeItems(count: 10),
            ),
            page: 4,
            perPage: 3,
        );

        self::assertSame(4, $paginator->nextPage);
    }

    public function testPreviousPageStepsBackByOne(): void
    {
        $paginator = new Paginator(
            paged: new ArrayPaged(
                items: $this->makeItems(count: 10),
            ),
            page: 3,
            perPage: 3,
        );

        self::assertSame(2, $paginator->previousPage);
    }

    public function testPreviousPageClampsToOne(): void
    {
        $paginator = new Paginator(
            paged: new ArrayPaged(
                items: $this->makeItems(count: 10),
            ),
            page: 1,
            perPage: 3,
        );

        self::assertSame(1, $paginator->previousPage);
    }

    public function testIterationYieldsFirstPage(): void
    {
        $paginator = new Paginator(
            paged: new ArrayPaged(
                items: $this->makeItems(count: 10),
            ),
            page: 1,
            perPage: 3,
        );

        $items = \iterator_to_array($paginator, preserve_keys: false);

        self::assertCount(3, $items);
        self::assertSame(1, $items[0]->id);
        self::assertSame(2, $items[1]->id);
        self::assertSame(3, $items[2]->id);
    }

    public function testIterationYieldsCorrectMiddlePage(): void
    {
        $paginator = new Paginator(
            paged: new ArrayPaged(
                items: $this->makeItems(count: 10),
            ),
            page: 2,
            perPage: 3,
        );

        $items = \iterator_to_array($paginator, preserve_keys: false);

        self::assertCount(3, $items);
        self::assertSame(4, $items[0]->id);
        self::assertSame(5, $items[1]->id);
        self::assertSame(6, $items[2]->id);
    }

    public function testIterationYieldsPartialLastPage(): void
    {
        $paginator = new Paginator(
            paged: new ArrayPaged(
                items: $this->makeItems(count: 10),
            ),
            page: 4,
            perPage: 3,
        );

        $items = \iterator_to_array($paginator, preserve_keys: false);

        self::assertCount(1, $items);
        self::assertSame(10, $items[0]->id);
    }

    public function testIterationOnEmptySourceYieldsNothing(): void
    {
        $paginator = new Paginator(
            paged: new ArrayPaged(
                items: [],
            ),
            page: 1,
            perPage: 10,
        );

        $items = \iterator_to_array($paginator, preserve_keys: false);

        self::assertCount(0, $items);
    }

    public function testPagedIsExposedAsPublicReadonly(): void
    {
        $paged = new ArrayPaged(
            items: $this->makeItems(count: 5),
        );

        $paginator = new Paginator(
            paged: $paged,
            page: 1,
            perPage: 2,
        );

        self::assertSame($paged, $paginator->paged);
    }

    public function testPageRangeIsEmptyForEmptySource(): void
    {
        $paginator = new Paginator(
            paged: new ArrayPaged(
                items: [],
            ),
            page: 1,
            perPage: 5,
        );

        self::assertSame(
            [],
            $paginator->pageRange(),
        );
    }

    public function testPageRangeReturnsAllPagesWhenWindowExceedsTotal(): void
    {
        $paginator = new Paginator(
            paged: new ArrayPaged(
                items: $this->makeItems(count: 8),
            ),
            page: 1,
            perPage: 3,
        );

        self::assertSame(
            [
                1,
                2,
                3,
            ],
            $paginator->pageRange(window: 10),
        );
    }

    public function testPageRangeCentersWindowOnMiddlePage(): void
    {
        $paginator = new Paginator(
            paged: new ArrayPaged(
                items: $this->makeItems(count: 100),
            ),
            page: 18,
            perPage: 5,
        );

        self::assertSame(
            [
                16,
                17,
                18,
                19,
                20,
            ],
            $paginator->pageRange(window: 5),
        );
    }

    public function testPageRangeShiftsRightNearStart(): void
    {
        $paginator = new Paginator(
            paged: new ArrayPaged(
                items: $this->makeItems(count: 100),
            ),
            page: 1,
            perPage: 5,
        );

        self::assertSame(
            [
                1,
                2,
                3,
                4,
                5,
            ],
            $paginator->pageRange(window: 5),
        );
    }

    public function testPageRangeShiftsRightNearStartWithPageTwo(): void
    {
        $paginator = new Paginator(
            paged: new ArrayPaged(
                items: $this->makeItems(count: 100),
            ),
            page: 2,
            perPage: 5,
        );

        self::assertSame(
            [
                1,
                2,
                3,
                4,
                5,
            ],
            $paginator->pageRange(window: 5),
        );
    }

    public function testPageRangeShiftsLeftNearEnd(): void
    {
        $paginator = new Paginator(
            paged: new ArrayPaged(
                items: $this->makeItems(count: 100),
            ),
            page: 20,
            perPage: 5,
        );

        self::assertSame(
            [
                16,
                17,
                18,
                19,
                20,
            ],
            $paginator->pageRange(window: 5),
        );
    }

    public function testPageRangeShiftsLeftNearEndWithPenultimatePage(): void
    {
        $paginator = new Paginator(
            paged: new ArrayPaged(
                items: $this->makeItems(count: 100),
            ),
            page: 19,
            perPage: 5,
        );

        self::assertSame(
            [
                16,
                17,
                18,
                19,
                20,
            ],
            $paginator->pageRange(window: 5),
        );
    }

    public function testPageRangeWithDefaultWindow(): void
    {
        $paginator = new Paginator(
            paged: new ArrayPaged(
                items: $this->makeItems(count: 100),
            ),
            page: 10,
            perPage: 5,
        );

        self::assertSame(
            [
                8,
                9,
                10,
                11,
                12,
            ],
            $paginator->pageRange(),
        );
    }

    public function testPageRangeClampsZeroWindowToOne(): void
    {
        $paginator = new Paginator(
            paged: new ArrayPaged(
                items: $this->makeItems(count: 100),
            ),
            page: 10,
            perPage: 5,
        );

        self::assertSame(
            [
                10,
            ],
            $paginator->pageRange(window: 0),
        );
    }

    public function testPageRangeClampsNegativeWindowToOne(): void
    {
        $paginator = new Paginator(
            paged: new ArrayPaged(
                items: $this->makeItems(count: 100),
            ),
            page: 10,
            perPage: 5,
        );

        self::assertSame(
            [10],
            $paginator->pageRange(window: -5),
        );
    }

    public function testPageRangeWithEvenWindowBiasesLeft(): void
    {
        $paginator = new Paginator(
            paged: new ArrayPaged(
                items: $this->makeItems(count: 100),
            ),
            page: 18,
            perPage: 5,
        );

        self::assertSame(
            [
                16,
                17,
                18,
                19,
            ],
            $paginator->pageRange(window: 4),
        );
    }

    /**
     * @return list<TestItem>
     */
    private function makeItems(
        int $count,
    ): array {
        $items = [];

        for ($i = 1; $i <= $count; $i++) {
            $items[] = new TestItem(
                id: $i,
            );
        }

        return $items;
    }
}
