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

namespace Fixture\View\Lumi;

use Tuxxedo\View\Lumi\Optimizer\OptimizerInterface;
use Tuxxedo\View\Lumi\Optimizer\OptimizerResult;
use Tuxxedo\View\Lumi\Optimizer\OptimizerResultInterface;
use Tuxxedo\View\Lumi\Parser\NodeStream;
use Tuxxedo\View\Lumi\Parser\NodeStreamInterface;
use Tuxxedo\View\Lumi\Syntax\Node\TextNode;

class RecordingOptimizer implements OptimizerInterface
{
    public int $callCount = 0;

    public function __construct(
        public int $changeCount = 0,
    ) {
    }

    public function optimize(
        NodeStreamInterface $stream,
    ): OptimizerResultInterface {
        $this->callCount++;

        if ($this->callCount <= $this->changeCount) {
            return OptimizerResult::create(
                oldStream: $stream,
                newStream: new NodeStream(
                    nodes: [
                        new TextNode(
                            text: 'mutated-' . $this->callCount,
                        ),
                    ],
                ),
            );
        }

        return OptimizerResult::create(
            oldStream: $stream,
            newStream: $stream,
        );
    }
}
