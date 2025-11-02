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

namespace Tuxxedo\View\Lumi\Optimizer\Evaluator;

use Tuxxedo\View\Lumi\Optimizer\Scope\Lattice;
use Tuxxedo\View\Lumi\Optimizer\Scope\ScopeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\AssignmentNode;
use Tuxxedo\View\Lumi\Syntax\Node\BinaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Node\ExpressionNodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\GroupNode;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Type;

class Evaluator implements EvaluatorInterface
{
    private readonly ExpressionReducerInterface $expressionReducer;

    public function __construct(
        ?ExpressionReducerInterface $expressionReducer = null,
    ) {
        $this->expressionReducer = $expressionReducer ?? new ExpressionReducer($this);
    }

    public function castValue(
        Type $type,
        string $value,
    ): string|int|float|bool|null {
        return match ($type) {
            Type::STRING => $value,
            Type::INT => \intval($value),
            Type::FLOAT => \floatval($value),
            Type::BOOL => $value === 'true',
            Type::NULL => null,
        };
    }

    public function castNode(
        Type $type,
        LiteralNode $node,
    ): LiteralNode {
        if ($node->type === $type) {
            return $node;
        }

        $value = $this->castValue($type, $node->operand);

        return new LiteralNode(
            operand: match (true) {
                \is_bool($value) => $value
                    ? 'true'
                    : 'false',
                \is_null($value) => 'null',
                default => \strval($value),
            },
            type: $type,
        );
    }

    public function castNodeToValue(
        LiteralNode $node,
    ): string|int|float|bool|null {
        return $this->castValue($node->type, $node->operand);
    }

    public function toString(
        LiteralNode $node,
    ): string {
        if ($node->type === Type::STRING) {
            return $node->operand;
        }

        /** @var string */
        return $this->castValue(Type::STRING, $node->operand);
    }

    public function toInt(
        LiteralNode $node,
    ): int {
        /** @var int */
        return $this->castValue(Type::INT, $node->operand);
    }

    public function toFloat(
        LiteralNode $node,
    ): float {
        /** @var float */
        return $this->castValue(Type::FLOAT, $node->operand);
    }

    public function toBool(
        LiteralNode $node,
    ): bool {
        if ($node->type === Type::BOOL) {
            return $node->operand === 'true'
                ? true
                : false;
        } elseif ($node->type === Type::NULL) {
            return false;
        }

        /** @var bool */
        return $this->castValue(Type::BOOL, $node->operand);
    }

    public function isTrue(
        ScopeInterface $scope,
        LiteralNode|IdentifierNode $node,
    ): EvaluatorResult {
        if ($node instanceof IdentifierNode) {
            $node = $this->dereferenceIdentifier($scope, $node);

            if (!$node instanceof LiteralNode) {
                return EvaluatorResult::UNKNOWN;
            }
        }

        if (
            $node->type === Type::BOOL &&
            $node->operand === 'true'
        ) {
            return EvaluatorResult::IS_TRUE;
        }

        return EvaluatorResult::IS_FALSE;
    }

    public function isFalse(
        ScopeInterface $scope,
        LiteralNode|IdentifierNode $node,
    ): EvaluatorResult {
        if ($node instanceof IdentifierNode) {
            $node = $this->dereferenceIdentifier($scope, $node);

            if (!$node instanceof LiteralNode) {
                return EvaluatorResult::UNKNOWN;
            }
        }

        if (
            $node->type === Type::BOOL &&
            $node->operand === 'false'
        ) {
            return EvaluatorResult::IS_TRUE;
        }

        return EvaluatorResult::IS_FALSE;
    }

    public function isTruthy(
        ScopeInterface $scope,
        LiteralNode|IdentifierNode $node,
    ): bool {
        if ($node instanceof IdentifierNode) {
            do {
                $node = $scope->get($node);
            } while ($node instanceof IdentifierNode);

            if (!$node instanceof LiteralNode) {
                return false;
            }
        }

        if ($node->type === Type::BOOL) {
            return $node->operand === 'true';
        }

        return $this->toBool($node);
    }

    public function isFalsy(
        ScopeInterface $scope,
        LiteralNode|IdentifierNode $node,
    ): bool {
        if ($node instanceof IdentifierNode) {
            do {
                $node = $scope->get($node);
            } while ($node instanceof IdentifierNode);

            if (!$node instanceof LiteralNode) {
                return false;
            }
        }

        if ($node->type === Type::BOOL) {
            return $node->operand === 'false';
        }

        return !$this->toBool($node);
    }

    public function isNull(
        ScopeInterface $scope,
        LiteralNode|IdentifierNode $node,
    ): bool {
        if ($node instanceof IdentifierNode) {
            do {
                $node = $scope->get($node);
            } while ($node instanceof IdentifierNode);

            if (!$node instanceof LiteralNode) {
                return false;
            }
        }

        if ($node->type === Type::NULL) {
            return true;
        }

        return false;
    }

    public function checkExpression(
        ScopeInterface $scope,
        ExpressionNodeInterface $node,
    ): EvaluatorResult {
        if ($node instanceof GroupNode) {
            $dereference = $this->dereferenceGroup($node);

            if (
                $dereference instanceof LiteralNode ||
                $dereference instanceof IdentifierNode
            ) {
                $node = $dereference;
            }
        }

        return match (true) {
            $node instanceof LiteralNode => $this->checkLiteral($node),
            $node instanceof IdentifierNode => $this->checkIdentifier($scope, $node),
            default => EvaluatorResult::UNKNOWN,
        };
    }

    public function checkLiteral(
        LiteralNode $node,
    ): EvaluatorResult {
        return match ($node->type) {
            Type::NULL => EvaluatorResult::IS_FALSE,
            default => $this->toBool($node)
                ? EvaluatorResult::IS_TRUE
                : EvaluatorResult::IS_FALSE,
        };
    }

    public function checkIdentifier(
        ScopeInterface $scope,
        IdentifierNode $node,
    ): EvaluatorResult {
        do {
            $node = $scope->get($node);
        } while ($node instanceof IdentifierNode);

        if (
            $node->lattice !== Lattice::UNDEF &&
            $node->value instanceof LiteralNode
        ) {
            return $this->checkLiteral($node->value);
        }

        return EvaluatorResult::UNKNOWN;
    }

    public function expression(
        ScopeInterface $scope,
        ExpressionNodeInterface $node,
    ): ?ExpressionNodeInterface {
        $node = $this->dereference($scope, $node);

        if ($node instanceof BinaryOpNode) {
            return $this->binaryOp($scope, $node);
        }

        if ($node instanceof AssignmentNode) {
            return $this->assignment($scope, $node);
        }

        return null;
    }

    public function binaryOp(
        ScopeInterface $scope,
        BinaryOpNode $node,
    ): ?ExpressionNodeInterface {
        return $this->expressionReducer->reduceBinaryOp($scope, $node);
    }

    public function assignment(
        ScopeInterface $scope,
        AssignmentNode $node,
    ): ?ExpressionNodeInterface {
        return $this->expressionReducer->reduceAssignment($scope, $node);
    }

    public function dereference(
        ScopeInterface $scope,
        ExpressionNodeInterface $node,
    ): ?ExpressionNodeInterface {
        if ($node instanceof GroupNode) {
            $node = $this->dereferenceGroup($node);
        }

        if ($node instanceof IdentifierNode) {
            $node = $this->dereferenceIdentifier($scope, $node);
        }

        return $node;
    }

    public function dereferenceGroup(
        GroupNode $node,
    ): ExpressionNodeInterface {
        do {
            $node = $node->operand;
        } while ($node instanceof GroupNode);

        return $node;
    }

    public function dereferenceIdentifier(
        ScopeInterface $scope,
        IdentifierNode $node,
    ): ?ExpressionNodeInterface {
        while ($node instanceof IdentifierNode) {
            $node = $scope->get($node)->value;
        }

        return $node;
    }
}
