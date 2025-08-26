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

namespace Tuxxedo\View\Lumi\Node;

// @todo Check if we should make $name an ExpressionNodeInterface
readonly class FunctionCallNode implements ExpressionNodeInterface
{
    /**
     * @param ExpressionNodeInterface[] $arguments
     */
    public function __construct(
        public string $name,
        public array $arguments,
    ) {
    }
}
