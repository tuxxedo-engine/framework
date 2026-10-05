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

namespace Unit\View\Lumi\Library\Standard\Filter;

use PHPUnit\Framework\TestCase;
use Tuxxedo\View\Lumi\Library\Standard\Filter\CollectionFilters;

class CollectionFiltersTest extends TestCase
{
    private CollectionFilters $filters;

    protected function setUp(): void
    {
        $this->filters = new CollectionFilters();
    }

    public function testCountCountsElements(): void
    {
        self::assertSame(
            3,
            $this->filters->count(
                [
                    'a',
                    'b',
                    'c',
                ],
            ),
        );
    }

    public function testCountReturnsZeroForEmptyArray(): void
    {
        self::assertSame(0, $this->filters->count([]));
    }

    public function testLengthReturnsStringLength(): void
    {
        self::assertSame(5, $this->filters->length('hello'));
    }

    public function testLengthReturnsArrayCount(): void
    {
        self::assertSame(
            3,
            $this->filters->length(
                [
                    'a',
                    'b',
                    'c',
                ],
            ),
        );
    }

    public function testLengthReturnsCountableCount(): void
    {
        $countable = new \ArrayObject(
            [
                1,
                2,
                3,
                4,
            ],
        );

        self::assertSame(4, $this->filters->length($countable));
    }

    public function testLengthHandlesMbString(): void
    {
        self::assertSame(5, $this->filters->length('héllo'));
    }
}
