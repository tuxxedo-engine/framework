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
use Tuxxedo\View\Lumi\Syntax\Node\BinaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Node\ExpressionNodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Operator\AssignmentSymbol;

class Variable implements VariableInterface
{
    public private(set) ExpressionNodeInterface $value;
    public private(set) Lattice $lattice;

    final private function __construct(
        public readonly string $name,
        ?ExpressionNodeInterface $value = null,
        ?ScopeInterface $scope = null,
    ) {
        if ($scope !== null && $value !== null) {
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
            $this->lattice = Lattice::CONST;

            return;
        }

        if ($value instanceof BinaryOpNode) {
            if (
                $value->left instanceof LiteralNode ||
                (
                    $value->left instanceof IdentifierNode &&
                    $scope->get($value->left)->lattice === Lattice::CONST
                )
            ) {
                if (
                    $value->right instanceof LiteralNode ||
                    (
                        $value->right instanceof IdentifierNode &&
                        $scope->get($value->right)->lattice === Lattice::CONST
                    )
                ) {
                    $this->lattice = Lattice::CONST;

                    return;
                }
            }
        }

        $this->lattice = Lattice::VARYING;
    }
}
