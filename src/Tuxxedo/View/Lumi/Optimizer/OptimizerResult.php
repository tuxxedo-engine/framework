<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\View\Lumi\Optimizer;

use Tuxxedo\View\Lumi\Parser\NodeStreamInterface;

class OptimizerResult implements OptimizerResultInterface
{
    final private function __construct(
        public readonly NodeStreamInterface $stream,
        public readonly bool $changed,
    ) {
    }

    public static function create(
        NodeStreamInterface $oldStream,
        NodeStreamInterface $newStream,
    ): static {
        $oldNodeCount = \sizeof($oldStream->nodes);
        $changed = false;

        for ($position = 0; $position < $oldNodeCount; $position++) {
            if (
                !\array_key_exists($position, $newStream->nodes) ||
                $oldStream->nodes[$position]::class !== $newStream->nodes[$position]::class ||
                \serialize($oldStream->nodes[$position]) !== \serialize($newStream->nodes[$position])
            ) {
                $changed = true;

                break;
            }
        }

        return new static(
            stream: $newStream,
            changed: $changed,
        );
    }
}
