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
use Tuxxedo\View\Lumi\Library\Standard\Filter\DebugFilters;

class DebugFiltersTest extends TestCase
{
    private DebugFilters $filters;

    protected function setUp(): void
    {
        $this->filters = new DebugFilters();
    }

    public function testDumpDumpsInteger(): void
    {
        self::assertSame('int(42)', $this->filters->dump(42));
    }

    public function testDumpDumpsString(): void
    {
        self::assertSame('string(5) "hello"', $this->filters->dump('hello'));
    }
}
