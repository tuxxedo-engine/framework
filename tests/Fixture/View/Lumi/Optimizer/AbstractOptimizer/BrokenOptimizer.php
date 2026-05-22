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

namespace Fixture\View\Lumi\Optimizer\AbstractOptimizer;

use Tuxxedo\View\Lumi\Optimizer\AbstractOptimizer;
use Tuxxedo\View\Lumi\Optimizer\OptimizerContext;
use Tuxxedo\View\Lumi\Parser\NodeStreamInterface;
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;

class BrokenOptimizer extends AbstractOptimizer
{
    protected function optimizeNode(
        NodeStreamInterface $stream,
        NodeInterface $node,
        ?OptimizerContext $context = null,
    ): array {
        return [
            $node,
        ];
    }

    public function callPopContext(): void
    {
        $this->popContext();
    }

    public function callPopScope(): void
    {
        $this->popScope();
    }
}
