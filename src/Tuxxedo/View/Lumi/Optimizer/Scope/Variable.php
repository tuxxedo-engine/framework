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

use Tuxxedo\View\Lumi\Syntax\Node\ArrayAccessNode;
use Tuxxedo\View\Lumi\Syntax\Node\AssignmentNode;
use Tuxxedo\View\Lumi\Syntax\Node\BinaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Node\ExpressionNodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Node\PropertyAccessNode;
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
    ) {
        if ($value !== null) {
            $this->mutate($scope, $value);
        } else {
            $this->lattice = Lattice::UNDEF;
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
        );

        $newVariable->lattice = $variable->lattice === Lattice::CONST
            ? Lattice::CONST
            : Lattice::VARYING;

        return $newVariable;
    }

    public function mutate(
        ScopeInterface $scope,
        ExpressionNodeInterface $value,
        AssignmentSymbol $operator = AssignmentSymbol::ASSIGN,
    ): void {
        unset($this->computedValue);

        $oldValue = $this->value;
        $this->value = $value;

        $dereferenced = $scope->evaluator->dereference($scope, $value);

        if ($dereferenced instanceof LiteralNode) {
            if (
                $oldValue !== null &&
                $operator !== AssignmentSymbol::ASSIGN &&
                (
                    $oldValue instanceof IdentifierNode ||
                    $oldValue instanceof PropertyAccessNode ||
                    $oldValue instanceof ArrayAccessNode
                )
            ) {
                $computedValue = $scope->evaluator->assignment(
                    scope: $scope,
                    node: new AssignmentNode(
                        name: $oldValue,
                        value: $dereferenced,
                        operator: $operator,
                    ),
                );

                if (
                    $computedValue !== null &&
                    $computedValue instanceof LiteralNode
                ) {
                    $this->lattice = Lattice::CONST;
                    $this->computedValue = $scope->evaluator->castNodeToValue($computedValue);
                } else {
                    $this->lattice = Lattice::VARYING;
                }
            } else {
                $this->lattice = Lattice::CONST;
                $this->computedValue = $scope->evaluator->castNodeToValue($dereferenced);
            }

            return;
        }

        if (
            $dereferenced instanceof IdentifierNode ||
            $dereferenced instanceof BinaryOpNode
        ) {
            $computedValue = $this->scope->evaluator->dereference($scope, $value);

            if ($computedValue instanceof LiteralNode) {
                $this->lattice = Lattice::CONST;
                $this->computedValue = $scope->evaluator->castNodeToValue($computedValue);

                return;
            }
        }

        $this->lattice = Lattice::VARYING;
    }

    public function hasComputedValue(): bool
    {
        return isset($this->computedValue);
    }
}
