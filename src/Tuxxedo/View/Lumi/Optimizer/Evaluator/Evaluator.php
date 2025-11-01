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
use Tuxxedo\View\Lumi\Syntax\Node\ExpressionNodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\GroupNode;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Type;

class Evaluator implements EvaluatorInterface
{
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

    public function evaluateExpression(
        ScopeInterface $scope,
        ExpressionNodeInterface $node,
    ): EvaluatorResult {
        // @todo Dereference nested GroupNodes with do-while
        if (
            $node instanceof GroupNode &&
            (
                $node->operand instanceof LiteralNode ||
                $node->operand instanceof IdentifierNode
            )
        ) {
            $node = $node->operand;
        }

        return match (true) {
            $node instanceof LiteralNode => $this->evaluateLiteral($node),
            $node instanceof IdentifierNode => $this->evaluateIdentifier($scope, $node),
            default => EvaluatorResult::UNKNOWN,
        };
    }

    public function evaluateLiteral(
        LiteralNode $node,
    ): EvaluatorResult {
        return match ($node->type) {
            Type::NULL => EvaluatorResult::IS_FALSE,
            default => $this->toBool($node)
                ? EvaluatorResult::IS_TRUE
                : EvaluatorResult::IS_FALSE,
        };
    }

    public function evaluateIdentifier(
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
            return $this->evaluateLiteral($node->value);
        }

        return EvaluatorResult::UNKNOWN;
    }
}
