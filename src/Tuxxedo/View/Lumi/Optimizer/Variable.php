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
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Operator\AssignmentSymbol;

class Variable implements VariableInterface
{
    public private(set) ExpressionNodeInterface $value;
    public private(set) VariableLattice $state;

    final private function __construct(
        public readonly string $name,
        ?ExpressionNodeInterface $value = null,
        ?ScopeInterface $scope = null,
    ) {
        if ($scope !== null && $value !== null) {
            $this->mutate($scope, $value);
        } else {
            $this->state = VariableLattice::UNDEF;
        }
    }

    public static function fromNewAssign(
        ScopeInterface $scope,
        AssignmentNode $node,
        IdentifierNode $name,
    ): static {
        return new static(
            name: $name->name,
            value: $node->value,
            scope: $scope,
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
        ScopeInterface $scope,
        ExpressionNodeInterface $value,
        AssignmentSymbol $operator = AssignmentSymbol::ASSIGN,
    ): void {
        $this->value = $value;

        if ($value instanceof LiteralNode) {
            $this->state = VariableLattice::CONST;

            return;
        }

        if ($value instanceof BinaryOpNode) {
            if (
                $value->left instanceof LiteralNode ||
                (
                    $value->left instanceof IdentifierNode &&
                    $scope->get($value->left)->state === VariableLattice::CONST
                )
            ) {
                if (
                    $value->right instanceof LiteralNode ||
                    (
                        $value->right instanceof IdentifierNode &&
                        $scope->get($value->right)->state === VariableLattice::CONST
                    )
                ) {
                    $this->state = VariableLattice::CONST;

                    return;
                }
            }
        }

        $this->state = VariableLattice::VARYING;
    }
}
