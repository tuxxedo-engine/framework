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

namespace Tuxxedo\View\Lumi\Optimizer\Scope;

use Tuxxedo\View\Lumi\Syntax\Node\AssignmentNode;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Operator\AssignmentSymbol;

class Scope implements ScopeInterface
{
    public private(set) array $variables = [];

    public function assign(
        AssignmentNode $node,
    ): void {
        if (!$node->name instanceof IdentifierNode) {
            return;
        }

        if (
            $node->operator !== AssignmentSymbol::ASSIGN &&
            \array_key_exists($node->name->name, $this->variables)
        ) {
            $this->variables[$node->name->name]->mutate(
                scope: $this,
                value: $node->value,
                operator: $node->operator,
            );
        } else {
            $this->variables[$node->name->name] = Variable::fromNewAssign(
                scope: $this,
                node: $node,
                name: $node->name,
            );
        }
    }

    public function get(
        IdentifierNode|string $name,
    ): VariableInterface {
        if (!\is_string($name)) {
            $name = $name->name;
        }

        return $this->variables[$name] ?? Variable::fromUndefined($name);
    }
}
