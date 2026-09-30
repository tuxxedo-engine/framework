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

namespace Tuxxedo\View\Lumi\Optimizer;

use Tuxxedo\View\Lumi\Parser\NodeStreamInterface;

readonly class OptimizerPipeline implements OptimizerPipelineInterface
{
    /**
     * @param OptimizerInterface[] $optimizers
     */
    public function __construct(
        public array $optimizers = [],
    ) {
    }

    public function run(
        NodeStreamInterface $stream,
    ): NodeStreamInterface {
        foreach ($this->optimizers as $optimizer) {
            do {
                $result = $optimizer->optimize(
                    stream: $stream,
                );

                $stream = $result->stream;
            } while ($result->changed);
        }

        return $stream;
    }
}
