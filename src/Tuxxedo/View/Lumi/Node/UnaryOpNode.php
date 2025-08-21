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

use Tuxxedo\View\Lumi\Syntax\UnaryOperator;

readonly class UnaryOpNode implements ExpressionNodeInterface
{
    public function __construct(
        public ExpressionNodeInterface $operand,
        public UnaryOperator $operator,
    ) {
    }
}
