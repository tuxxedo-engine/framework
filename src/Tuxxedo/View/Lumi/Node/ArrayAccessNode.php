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

// @todo May need to implement IterableExpressionNodeInterface
readonly class ArrayAccessNode implements ExpressionNodeInterface
{
    public string $kind;

    public function __construct(
        public ExpressionNodeInterface $array,
        public ExpressionNodeInterface $key,
    ) {
        $this->kind = BuiltinNodeKinds::EXPRESSION->name;
    }
}
