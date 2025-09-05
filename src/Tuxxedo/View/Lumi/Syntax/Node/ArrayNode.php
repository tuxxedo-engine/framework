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

namespace Tuxxedo\View\Lumi\Syntax\Node;

// @todo May need to implement IterableExpressionNodeInterface

readonly class ArrayNode implements ExpressionNodeInterface
{
    public string $kind;

    /**
     * @param ArrayItemNode[] $items
     */
    public function __construct(
        public array $items,
    ) {
        $this->kind = BuiltinNodeKinds::EXPRESSION->name;
    }
}
