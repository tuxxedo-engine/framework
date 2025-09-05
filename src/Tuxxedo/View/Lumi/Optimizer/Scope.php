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

use Tuxxedo\View\Lumi\Syntax\Node\AssignmentNode;
use Tuxxedo\View\Lumi\Syntax\Operator\AssignmentOperator;

class Scope implements ScopeInterface
{
    public private(set) array $variables = [];

    public function assign(
        AssignmentNode $node,
    ): void {
        if (
            $node->operator !== AssignmentOperator::ASSIGN &&
            \array_key_exists($node->name->name, $this->variables)
        ) {
            $this->variables[$node->name->name]->mutate(
                $this,
                $node->value,
                $node->operator,
            );
        } else {
            $this->variables[$node->name->name] = Variable::fromNewAssign($this, $node);
        }
    }

    public function get(
        string $name,
    ): VariableInterface {
        return $this->variables[$name] ?? Variable::fromUndefined($name);
    }
}
