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

namespace Unit\View\Lumi\Optimizer;

use Fixture\View\Lumi\Optimizer\AbstractOptimizer\BrokenOptimizer;
use PHPUnit\Framework\TestCase;
use Tuxxedo\View\Lumi\Optimizer\OptimizerException;

class AbstractOptimizerTest extends TestCase
{
    public function testPopContextThrowsOnEmptyContextStack(): void
    {
        $optimizer = new BrokenOptimizer();

        self::expectException(OptimizerException::class);

        $optimizer->callPopContext();
    }

    public function testPopScopeThrowsOnEmptyScopeStack(): void
    {
        $optimizer = new BrokenOptimizer();

        self::expectException(OptimizerException::class);

        $optimizer->callPopScope();
    }
}
