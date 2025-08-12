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

namespace Tuxxedo\View\Lumi\Parser\Node;

readonly class BinaryOpNode implements ExpressionNodeInterface
{
    public function __construct(
        public ExpressionNodeInterface $op1,
        public ExpressionNodeInterface $op2,
        public BinaryOperator $operator,
    ) {
    }
}
