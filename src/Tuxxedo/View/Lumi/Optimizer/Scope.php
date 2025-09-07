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
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\PropertyAccessNode;
use Tuxxedo\View\Lumi\Syntax\Operator\AssignmentSymbol;

class Scope implements ScopeInterface
{
    public private(set) array $variables = [];

    public function assign(
        AssignmentNode $node,
    ): void {
        $name = $this->name($node->name);

        if (
            $node->operator !== AssignmentSymbol::ASSIGN &&
            \array_key_exists($name, $this->variables)
        ) {
            $this->variables[$name]->mutate(
                scope: $this,
                value: $node->value,
                operator: $node->operator,
            );
        } else {
            $this->variables[$name] = Variable::fromNewAssign(
                scope: $this,
                node: $node,
            );
        }
    }

    public function name(
        IdentifierNode|PropertyAccessNode $node,
    ): string {
        if ($node instanceof IdentifierNode) {
            return $node->name;
        }

        return \sprintf(
            '%s::%s',
            $node->accessor->name,
            $node->property,
        );
    }

    public function get(
        IdentifierNode|PropertyAccessNode|string $name,
    ): VariableInterface {
        if (!\is_string($name)) {
            $name = $this->name($name);
        }

        return $this->variables[$name] ?? Variable::fromUndefined($name);
    }
}
