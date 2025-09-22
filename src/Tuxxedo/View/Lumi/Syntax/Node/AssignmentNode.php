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

use Tuxxedo\View\Lumi\Syntax\Operator\AssignmentSymbol;

readonly class AssignmentNode implements NodeInterface
{
    public array $scopes;

    public function __construct(
        public IdentifierNode|PropertyAccessNode|ArrayAccessNode $name,
        public ExpressionNodeInterface $value,
        public AssignmentSymbol $operator,
    ) {
        $this->scopes = [
            BuiltinNodeScopes::ROOT->name,
            BuiltinNodeScopes::BLOCK->name,
        ];
    }
}
