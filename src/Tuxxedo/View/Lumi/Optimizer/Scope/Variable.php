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

use Tuxxedo\View\Lumi\Syntax\NativeType;
use Tuxxedo\View\Lumi\Syntax\Node\AssignmentNode;
use Tuxxedo\View\Lumi\Syntax\Node\BinaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Node\ExpressionNodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Operator\AssignmentSymbol;

class Variable implements VariableInterface
{
    public private(set) ?LiteralNode $value = null;
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

    private function castTo(
        LiteralNode $value,
        NativeType $type,
    ): LiteralNode {
        if ($value->type === $type) {
            return $value;
        }

        $newValue = $type->cast($value->operand);

        return new LiteralNode(
            operand: match (true) {
                \is_bool($newValue) => $newValue ? 'true' : 'false',
                \is_null($newValue) => 'null',
                default => \strval($newValue),
            },
            type: $type,
        );
    }

    private function castToString(
        LiteralNode $value,
    ): LiteralNode {
        return $this->castTo($value, NativeType::STRING);
    }

    private function getOperatorMutatedLiteral(
        LiteralNode $value,
        AssignmentSymbol $operator,
    ): LiteralNode {
        if (
            $this->value === null ||
            $operator === AssignmentSymbol::ASSIGN
        ) {
            return $value;
        }

        if ($operator === AssignmentSymbol::CONCAT) {
            if ($this->value->operand === '') {
                return $this->castToString($value);
            }

            return new LiteralNode(
                operand: $this->castToString($this->value)->operand . $this->castToString($value)->operand,
                type: NativeType::STRING,
            );
        }

        if ($operator === AssignmentSymbol::NULL_ASSIGN) {
            return $this->value->type === NativeType::NULL
                ? $value
                : $this->value;
        }

        return match ($operator) {
            // @todo Support ADD
            // @todo Support SUBTRACT
            // @todo Support MULTIPLY
            // @todo Support DIVIDE
            // @todo Support MODULUS
            // @todo Support EXPONENTIATE
            // @todo Support BITWISE_AND
            // @todo Support BITWISE_OR
            // @todo Support BITWISE_XOR
            // @todo Support SHIFT_LEFT
            // @todo Support SHIFT_RIGHT
            default => $value,
        };
    }

    public function mutate(
        ScopeInterface $scope,
        ExpressionNodeInterface $value,
        AssignmentSymbol $operator = AssignmentSymbol::ASSIGN,
    ): void {
        $this->value = $value instanceof LiteralNode
            ? $this->getOperatorMutatedLiteral($value, $operator)
            : null;

        if ($value instanceof LiteralNode) {
            $this->lattice = Lattice::CONST;

            return;
        }

        // @todo This does not work anymore
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
