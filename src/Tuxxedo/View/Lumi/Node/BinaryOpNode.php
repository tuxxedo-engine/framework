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

use Tuxxedo\View\Lumi\Syntax\BinaryOperator;

readonly class BinaryOpNode implements ExpressionNodeInterface
{
    public string $kind;

    public function __construct(
        public ExpressionNodeInterface $left,
        public ExpressionNodeInterface $right,
        public BinaryOperator $operator,
    ) {
        $this->kind = BuiltinNodeKinds::EXPRESSION->name;
    }
}
