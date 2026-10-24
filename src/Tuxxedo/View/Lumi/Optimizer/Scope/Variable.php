<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2026 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\View\Lumi\Optimizer\Scope;

use Tuxxedo\View\Lumi\Syntax\Node\AssignmentNode;
use Tuxxedo\View\Lumi\Syntax\Node\BinaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Node\ExpressionNodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Node\TernaryNode;
use Tuxxedo\View\Lumi\Syntax\Operator\AssignmentSymbol;

class Variable implements VariableInterface
{
    public private(set) ?ExpressionNodeInterface $value = null;
    public private(set) Lattice $lattice;

    public private(set) string|int|float|bool|null $computedValue;

    final private function __construct(
        public readonly ScopeInterface $scope,
        public readonly string $name,
        ?ExpressionNodeInterface $value = null,
        Lattice $lattice = Lattice::UNDEF,
    ) {
        if ($value !== null) {
            $this->mutate($scope, $value);
        } else {
            $this->lattice = $lattice;
        }
    }

    public static function fromNewAssign(
        ScopeInterface $scope,
        AssignmentNode $node,
        IdentifierNode $name,
    ): static {
        return new static(
            scope: $scope,
            name: $name->name,
            value: $node->value,
        );
    }

    public static function fromUndefined(
        ScopeInterface $scope,
        string $name,
    ): static {
        return new static(
            scope: $scope,
            name: $name,
            lattice: Lattice::UNDEF,
        );
    }

    public static function fromVarying(
        Scope $scope,
        string $name,
    ): static {
        return new static(
            scope: $scope,
            name: $name,
            lattice: Lattice::VARYING,
        );
    }

    public static function fromExisting(
        ScopeInterface $scope,
        VariableInterface $variable,
    ): static {
        $newVariable = new static(
            scope: $scope,
            name: $variable->name,
            value: null,
            lattice: Lattice::VARYING,
        );

        return $newVariable;
    }

    public function mutate(
        ScopeInterface $scope,
        ExpressionNodeInterface $value,
        AssignmentSymbol $operator = AssignmentSymbol::ASSIGN,
    ): void {
        if ($operator !== AssignmentSymbol::ASSIGN) {
            $this->mutateCompound(
                scope: $scope,
                value: $value,
                operator: $operator,
            );

            return;
        }

        unset($this->computedValue);

        $dereferenced = $scope->evaluator->dereference($scope, $value);

        if ($dereferenced instanceof LiteralNode) {
            $this->lattice = Lattice::CONST;
            $this->computedValue = $scope->evaluator->castNodeToValue($dereferenced);
            $this->value = $dereferenced;

            return;
        }

        $computedValue = null;

        if ($dereferenced instanceof BinaryOpNode) {
            $computedValue = $scope->evaluator->binaryOp($scope, $dereferenced);
        } elseif ($dereferenced instanceof TernaryNode) {
            $computedValue = $scope->evaluator->expression($scope, $dereferenced);
        } elseif ($dereferenced instanceof IdentifierNode) {
            $computedValue = $scope->evaluator->dereference($scope, $value);
        }

        if ($computedValue instanceof LiteralNode) {
            $this->lattice = Lattice::CONST;
            $this->computedValue = $scope->evaluator->castNodeToValue($computedValue);
            $this->value = $computedValue;

            return;
        }

        $this->lattice = Lattice::VARYING;
        $this->value = null;
    }

    private function mutateCompound(
        ScopeInterface $scope,
        ExpressionNodeInterface $value,
        AssignmentSymbol $operator,
    ): void {
        $computed = null;

        if ($this->hasComputedValue()) {
            $right = $scope->evaluator->expression($scope, $value);

            if ($right instanceof LiteralNode) {
                $computed = $scope->evaluator->assignment(
                    scope: $scope,
                    node: new AssignmentNode(
                        name: new IdentifierNode(
                            name: $this->name,
                        ),
                        value: $right,
                        operator: $operator,
                    ),
                );
            }
        }

        unset($this->computedValue);

        if ($computed instanceof LiteralNode) {
            $this->lattice = Lattice::CONST;
            $this->computedValue = $scope->evaluator->castNodeToValue($computed);
            $this->value = $computed;

            return;
        }

        $this->lattice = Lattice::VARYING;
        $this->value = null;
    }

    public function hasComputedValue(): bool
    {
        return isset($this->computedValue);
    }
}
