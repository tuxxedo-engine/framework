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

use Tuxxedo\View\Lumi\Syntax\Operator\UnarySymbol;

// @todo Support in Compiler
readonly class UnaryOpNode implements ExpressionNodeInterface
{
    public array $scopes;

    public function __construct(
        public ExpressionNodeInterface $operand,
        public UnarySymbol $operator,
    ) {
        $this->scopes = [
            BuiltinNodeScopes::EXPRESSION->name,
        ];
    }
}
