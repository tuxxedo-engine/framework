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
use Tuxxedo\View\Lumi\Syntax\Node\BinaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Node\ExpressionNodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Operator\AssignmentOperator;

class Variable implements VariableInterface
{
    public private(set) ExpressionNodeInterface $value;
    public private(set) VariableState $state;

    final private function __construct(
        public readonly string $name,
        ?ExpressionNodeInterface $value = null,
    ) {
        if ($value !== null) {
            $this->mutate($value);
        } else {
            $this->state = VariableState::UNDEF;
        }
    }

    public static function fromNode(
        AssignmentNode $node,
    ): static {
        return new static(
            name: $node->name->name,
            value: $node->value,
        );
    }

    public static function fromUndefined(
        string $name,
    ): static {
        return new static(
            name: $name,
        );
    }

    public function mutate(
        ExpressionNodeInterface $value,
        AssignmentOperator $operator = AssignmentOperator::ASSIGN,
    ): void {
        $state = VariableState::VARYING;

        if (
            $value instanceof LiteralNode ||
            (
                $value instanceof BinaryOpNode &&
                $value->left instanceof LiteralNode &&
                $value->right instanceof LiteralNode &&
                $operator !== AssignmentOperator::NULL_ASSIGN
            )
        ) {
            $state = VariableState::CONST;
        }

        $this->value = $value;
        $this->state = $state;
    }
}
