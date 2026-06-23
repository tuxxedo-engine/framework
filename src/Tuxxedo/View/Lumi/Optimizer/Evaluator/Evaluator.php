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

namespace Tuxxedo\View\Lumi\Optimizer\Evaluator;

use Tuxxedo\View\Lumi\Optimizer\Scope\Lattice;
use Tuxxedo\View\Lumi\Optimizer\Scope\ScopeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\AssignmentNode;
use Tuxxedo\View\Lumi\Syntax\Node\BinaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Node\ExpressionNodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\GroupNode;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Node\UnaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Operator\AssignmentSymbol;
use Tuxxedo\View\Lumi\Syntax\Operator\BinarySymbol;
use Tuxxedo\View\Lumi\Syntax\Operator\UnarySymbol;
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

        if ($type === Type::BOOL) {
            return LiteralNode::createBool($this->toBool($node));
        }

        $value = $this->castValue($type, $node->operand);

        return new LiteralNode(
            operand: $value === null
                ? 'null'
                : \strval($value),
            type: $type,
        );
    }

    public function castNodeToValue(
        LiteralNode $node,
    ): string|int|float|bool|null {
        return $this->castValue($node->type, $node->operand);
    }

    public function castNodeToNumeric(
        LiteralNode $node,
    ): int|float|null {
        $local = $this->castNodeToValue($node);

        if (\is_string($local)) {
            if (!\is_numeric($local)) {
                return null;
            }

            if (\str_contains($local, '.')) {
                return \floatval($local);
            }

            return \intval($local);
        } elseif (
            !\is_int($local) &&
            !\is_float($local)
        ) {
            return \intval($local);
        }

        return $local;
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

        return \boolval($this->castNodeToValue($node));
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
            $node = $this->dereferenceIdentifier($scope, $node);

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
            $node = $this->dereferenceIdentifier($scope, $node);

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
            $node = $this->dereferenceIdentifier($scope, $node);

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
        } elseif ($node->hasComputedValue()) {
            return \boolval($node->computedValue)
                ? EvaluatorResult::IS_TRUE
                : EvaluatorResult::IS_FALSE;
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

        if ($node instanceof UnaryOpNode) {
            return $this->unaryOp($scope, $node);
        }

        if ($node instanceof LiteralNode) {
            return $node;
        }

        return null;
    }

    public function binaryOp(
        ScopeInterface $scope,
        BinaryOpNode $node,
    ): ?ExpressionNodeInterface {
        $reducer = match ($node->operator) {
            BinarySymbol::CONCAT => $this->reduceConcat(...),
            BinarySymbol::ADD => $this->reduceAdd(...),
            BinarySymbol::SUBTRACT => $this->reduceSubtract(...),
            BinarySymbol::MULTIPLY => $this->reduceMultiply(...),
            BinarySymbol::DIVIDE => $this->reduceDivide(...),
            BinarySymbol::MODULUS => $this->reduceModulus(...),
            BinarySymbol::STRICT_EQUAL_IMPLICIT, BinarySymbol::STRICT_EQUAL_EXPLICIT => $this->reduceEqual(...),
            BinarySymbol::STRICT_NOT_EQUAL_IMPLICIT, BinarySymbol::STRICT_NOT_EQUAL_EXPLICIT => $this->reduceNotEqual(...),
            BinarySymbol::GREATER => $this->reduceGreater(...),
            BinarySymbol::LESS => $this->reduceLess(...),
            BinarySymbol::GREATER_EQUAL => $this->reduceGreaterEqual(...),
            BinarySymbol::LESS_EQUAL => $this->reduceLessEqual(...),
            BinarySymbol::AND => $this->reduceAnd(...),
            BinarySymbol::OR => $this->reduceOr(...),
            BinarySymbol::XOR => $this->reduceXor(...),
            BinarySymbol::EXPONENTIATE => $this->reduceExponentiate(...),
            BinarySymbol::BITWISE_AND => $this->reduceBitwiseAnd(...),
            BinarySymbol::BITWISE_OR => $this->reduceBitwiseOr(...),
            BinarySymbol::BITWISE_XOR => $this->reduceBitwiseXor(...),
            BinarySymbol::BITWISE_SHIFT_LEFT => $this->reduceBitwiseShiftLeft(...),
            BinarySymbol::BITWISE_SHIFT_RIGHT => $this->reduceBitwiseShiftRight(...),
            BinarySymbol::NULL_COALESCE => $this->reduceNullCoalesce(...),
            default => null,
        };

        if ($reducer !== null) {
            return $reducer($scope, $node->left, $node->right);
        }

        return null;
    }

    public function unaryOp(
        ScopeInterface $scope,
        UnaryOpNode $node,
    ): ?ExpressionNodeInterface {
        return (match ($node->operator) {
            UnarySymbol::NOT => $this->reduceNot(...),
            UnarySymbol::NEGATE => $this->reduceNegate(...),
            UnarySymbol::BITWISE_NOT => $this->reduceBitwiseNot(...),
            UnarySymbol::INCREMENT_PRE => $this->reduceIncrementPre(...),
            UnarySymbol::INCREMENT_POST => $this->reduceIncrementPost(...),
            UnarySymbol::DECREMENT_PRE => $this->reduceDecrementPre(...),
            UnarySymbol::DECREMENT_POST => $this->reduceDecrementPost(...),
        })($scope, $node->operand);
    }

    public function assignment(
        ScopeInterface $scope,
        AssignmentNode $node,
    ): ?ExpressionNodeInterface {
        $left = $this->resolve($scope, $node->name);

        if (!$left instanceof LiteralNode) {
            return null;
        }

        $reducer = match ($node->operator) {
            AssignmentSymbol::CONCAT => $this->reduceConcat(...),
            AssignmentSymbol::NULL_ASSIGN => $this->reduceNullCoalesce(...),
            AssignmentSymbol::ADD => $this->reduceAdd(...),
            AssignmentSymbol::SUBTRACT => $this->reduceSubtract(...),
            AssignmentSymbol::MULTIPLY => $this->reduceMultiply(...),
            AssignmentSymbol::DIVIDE => $this->reduceDivide(...),
            AssignmentSymbol::MODULUS => $this->reduceModulus(...),
            AssignmentSymbol::EXPONENTIATE => $this->reduceExponentiate(...),
            AssignmentSymbol::BITWISE_AND => $this->reduceBitwiseAnd(...),
            AssignmentSymbol::BITWISE_OR => $this->reduceBitwiseOr(...),
            AssignmentSymbol::BITWISE_XOR => $this->reduceBitwiseXor(...),
            AssignmentSymbol::BITWISE_SHIFT_LEFT => $this->reduceBitwiseShiftLeft(...),
            AssignmentSymbol::BITWISE_SHIFT_RIGHT => $this->reduceBitwiseShiftRight(...),
            default => null,
        };

        if ($reducer !== null) {
            return $reducer($scope, $left, $node->value);
        }

        return null;
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

    private function resolve(
        ScopeInterface $scope,
        ExpressionNodeInterface $node,
    ): ?ExpressionNodeInterface {
        if ($node instanceof LiteralNode) {
            return $node;
        }

        if ($node instanceof IdentifierNode) {
            $variable = $scope->get($node);

            if ($variable->hasComputedValue()) {
                return LiteralNode::createFromNativeType($variable->computedValue);
            }
        }

        return $this->expression($scope, $node);
    }

    private function resolveNumber(
        ScopeInterface $scope,
        ExpressionNodeInterface $node,
        int|float &$value,
    ): bool {
        if (!$node instanceof LiteralNode) {
            $node = $this->resolve($scope, $node);

            if (!$node instanceof LiteralNode) {
                return false;
            }
        }

        $local = $this->castNodeToNumeric($node);

        if ($local === null) {
            return false;
        }

        $value = $local;

        return true;
    }

    private function resolveBool(
        ScopeInterface $scope,
        ExpressionNodeInterface $node,
    ): ?bool {
        $node = $this->resolve($scope, $node);

        if (!$node instanceof LiteralNode) {
            return null;
        }

        return $this->toBool($node);
    }

    public function reduceConcat(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface {
        $left = $this->resolve($scope, $left);
        $right = $this->resolve($scope, $right);

        if (
            !$left instanceof LiteralNode ||
            !$right instanceof LiteralNode
        ) {
            return null;
        }

        return LiteralNode::createString(
            value: $this->castNodeToValue($left) . $this->castNodeToValue($right),
        );
    }

    public function reduceAdd(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface {
        $leftValue = 0;
        $rightValue = 0;

        if (
            !$this->resolveNumber($scope, $left, $leftValue) ||
            !$this->resolveNumber($scope, $right, $rightValue)
        ) {
            return null;
        }

        return LiteralNode::createFromNativeType($leftValue + $rightValue);
    }

    public function reduceSubtract(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface {
        $leftValue = 0;
        $rightValue = 0;

        if (
            !$this->resolveNumber($scope, $left, $leftValue) ||
            !$this->resolveNumber($scope, $right, $rightValue)
        ) {
            return null;
        }

        return LiteralNode::createFromNativeType($leftValue - $rightValue);
    }

    public function reduceMultiply(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface {
        $leftValue = 0;
        $rightValue = 0;

        if (
            !$this->resolveNumber($scope, $left, $leftValue) ||
            !$this->resolveNumber($scope, $right, $rightValue)
        ) {
            return null;
        }

        return LiteralNode::createFromNativeType($leftValue * $rightValue);
    }

    public function reduceDivide(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface {
        $leftValue = 0;
        $rightValue = 0;

        if (
            !$this->resolveNumber($scope, $left, $leftValue) ||
            !$this->resolveNumber($scope, $right, $rightValue)
        ) {
            return null;
        }

        if ($rightValue === 0 || $rightValue === 0.0) {
            return null;
        }

        return LiteralNode::createFromNativeType($leftValue / $rightValue);
    }

    public function reduceModulus(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface {
        $leftValue = 0;
        $rightValue = 0;

        if (
            !$this->resolveNumber($scope, $left, $leftValue) ||
            !$this->resolveNumber($scope, $right, $rightValue)
        ) {
            return null;
        }

        if (\is_float($leftValue) || \is_float($rightValue)) {
            $leftValue = (float) $leftValue;
            $rightValue = (float) $rightValue;

            if ($rightValue === 0.0) {
                return null;
            }

            return LiteralNode::createFromNativeType(\fmod($leftValue, $rightValue));
        }

        if (\intval($rightValue) === 0) {
            return null;
        }

        return LiteralNode::createFromNativeType($leftValue % $rightValue);
    }

    public function reduceEqual(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface {
        $left = $this->resolve($scope, $left);
        $right = $this->resolve($scope, $right);

        if (
            !$left instanceof LiteralNode ||
            !$right instanceof LiteralNode
        ) {
            return null;
        }

        if ($left->type !== $right->type) {
            return LiteralNode::createBool(false);
        }

        return LiteralNode::createBool($left->operand === $right->operand);
    }

    public function reduceNotEqual(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface {
        $left = $this->resolve($scope, $left);
        $right = $this->resolve($scope, $right);

        if (
            !$left instanceof LiteralNode ||
            !$right instanceof LiteralNode
        ) {
            return null;
        }

        if ($left->type !== $right->type) {
            return LiteralNode::createBool(true);
        }

        return LiteralNode::createBool($left->operand !== $right->operand);
    }

    public function reduceGreater(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface {
        $leftValue = 0;
        $rightValue = 0;

        if (
            !$this->resolveNumber($scope, $left, $leftValue) ||
            !$this->resolveNumber($scope, $right, $rightValue)
        ) {
            return null;
        }

        return LiteralNode::createBool($leftValue > $rightValue);
    }

    public function reduceLess(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface {
        $leftValue = 0;
        $rightValue = 0;

        if (
            !$this->resolveNumber($scope, $left, $leftValue) ||
            !$this->resolveNumber($scope, $right, $rightValue)
        ) {
            return null;
        }

        return LiteralNode::createBool($leftValue < $rightValue);
    }

    public function reduceGreaterEqual(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface {
        $leftValue = 0;
        $rightValue = 0;

        if (
            !$this->resolveNumber($scope, $left, $leftValue) ||
            !$this->resolveNumber($scope, $right, $rightValue)
        ) {
            return null;
        }

        return LiteralNode::createBool($leftValue >= $rightValue);
    }

    public function reduceLessEqual(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface {
        $leftValue = 0;
        $rightValue = 0;

        if (
            !$this->resolveNumber($scope, $left, $leftValue) ||
            !$this->resolveNumber($scope, $right, $rightValue)
        ) {
            return null;
        }

        return LiteralNode::createBool($leftValue <= $rightValue);
    }

    public function reduceAnd(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface {
        $left = $this->resolveBool($scope, $left);
        $right = $this->resolveBool($scope, $right);

        if ($left === null || $right === null) {
            return null;
        }

        return LiteralNode::createBool($left && $right);
    }

    public function reduceOr(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface {
        $left = $this->resolveBool($scope, $left);
        $right = $this->resolveBool($scope, $right);

        if ($left === null || $right === null) {
            return null;
        }

        return LiteralNode::createBool($left || $right);
    }

    public function reduceXor(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface {
        $left = $this->resolveBool($scope, $left);
        $right = $this->resolveBool($scope, $right);

        if ($left === null || $right === null) {
            return null;
        }

        return LiteralNode::createBool($left xor $right);
    }

    public function reduceExponentiate(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface {
        $leftValue = 0;
        $rightValue = 0;

        if (
            !$this->resolveNumber($scope, $left, $leftValue) ||
            !$this->resolveNumber($scope, $right, $rightValue)
        ) {
            return null;
        }

        $leftValue = (int) $leftValue;
        $rightValue = (int) $rightValue;

        if ($leftValue === 0 && $rightValue < 0) {
            return null;
        }

        return LiteralNode::createFromNativeType($leftValue ** $rightValue);
    }

    public function reduceBitwiseAnd(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface {
        $leftValue = 0;
        $rightValue = 0;

        if (
            !$this->resolveNumber($scope, $left, $leftValue) ||
            !$this->resolveNumber($scope, $right, $rightValue)
        ) {
            return null;
        }

        return LiteralNode::createInt($leftValue & $rightValue);
    }

    public function reduceBitwiseOr(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface {
        $leftValue = 0;
        $rightValue = 0;

        if (
            !$this->resolveNumber($scope, $left, $leftValue) ||
            !$this->resolveNumber($scope, $right, $rightValue)
        ) {
            return null;
        }

        return LiteralNode::createInt($leftValue | $rightValue);
    }

    public function reduceBitwiseXor(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface {
        $leftValue = 0;
        $rightValue = 0;

        if (
            !$this->resolveNumber($scope, $left, $leftValue) ||
            !$this->resolveNumber($scope, $right, $rightValue)
        ) {
            return null;
        }

        return LiteralNode::createInt($leftValue ^ $rightValue);
    }

    public function reduceBitwiseShiftLeft(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface {
        $leftValue = 0;
        $rightValue = 0;

        if (
            !$this->resolveNumber($scope, $left, $leftValue) ||
            !$this->resolveNumber($scope, $right, $rightValue)
        ) {
            return null;
        }

        return LiteralNode::createFromNativeType($leftValue << $rightValue);
    }

    public function reduceBitwiseShiftRight(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface {
        $leftValue = 0;
        $rightValue = 0;

        if (
            !$this->resolveNumber($scope, $left, $leftValue) ||
            !$this->resolveNumber($scope, $right, $rightValue)
        ) {
            return null;
        }

        return LiteralNode::createFromNativeType($leftValue >> $rightValue);
    }

    public function reduceNullCoalesce(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface {
        $left = $this->resolve($scope, $left);

        if (
            $left instanceof LiteralNode &&
            $left->type === Type::NULL
        ) {
            return $right;
        }

        return null;
    }

    public function reduceNot(
        ScopeInterface $scope,
        ExpressionNodeInterface $expression,
    ): ?ExpressionNodeInterface {
        $expression = $this->resolve($scope, $expression);

        if ($expression instanceof LiteralNode) {
            return LiteralNode::createBool(!\boolval($this->castNodeToValue($expression)));
        }

        return null;
    }

    public function reduceNegate(
        ScopeInterface $scope,
        ExpressionNodeInterface $expression,
    ): ?ExpressionNodeInterface {
        $expression = $this->resolve($scope, $expression);

        if (
            $expression instanceof LiteralNode &&
            (
                $expression->type === Type::INT ||
                $expression->type === Type::FLOAT
            )
        ) {
            $value = $this->castNodeToNumeric($expression);

            if ($value !== null) {
                return LiteralNode::createFromNativeType(-$value);
            }
        }

        return null;
    }

    public function reduceBitwiseNot(
        ScopeInterface $scope,
        ExpressionNodeInterface $expression,
    ): ?ExpressionNodeInterface {
        $expression = $this->resolve($scope, $expression);

        if ($expression instanceof LiteralNode) {
            return LiteralNode::createInt(~((int) $this->castNodeToValue($expression)));
        }

        return null;
    }

    public function reduceIncrementPre(
        ScopeInterface $scope,
        ExpressionNodeInterface $expression,
    ): ?ExpressionNodeInterface {
        if (!$expression instanceof IdentifierNode) {
            return null;
        }

        $variable = $scope->get($expression);

        if (!$variable->hasComputedValue()) {
            return null;
        }

        $numericValue = $this->castNodeToNumeric(
            node: LiteralNode::createFromNativeType($variable->computedValue),
        );

        if ($numericValue === null) {
            return null;
        }

        $value = LiteralNode::createFromNativeType(
            value: 1 + $numericValue,
        );

        $variable->mutate($scope, $value);

        return $value;
    }

    public function reduceIncrementPost(
        ScopeInterface $scope,
        ExpressionNodeInterface $expression,
    ): ?ExpressionNodeInterface {
        if (!$expression instanceof IdentifierNode) {
            return null;
        }

        $variable = $scope->get($expression);

        if (!$variable->hasComputedValue()) {
            return null;
        }

        $computedValue = LiteralNode::createFromNativeType($variable->computedValue);
        $numericValue = $this->castNodeToNumeric($computedValue);

        if ($numericValue === null) {
            return null;
        }

        $value = LiteralNode::createFromNativeType(
            value: 1 + $numericValue,
        );

        $variable->mutate($scope, $value);

        return $computedValue;
    }

    public function reduceDecrementPre(
        ScopeInterface $scope,
        ExpressionNodeInterface $expression,
    ): ?ExpressionNodeInterface {
        if (!$expression instanceof IdentifierNode) {
            return null;
        }

        $variable = $scope->get($expression);

        if (!$variable->hasComputedValue()) {
            return null;
        }

        $numericValue = $this->castNodeToNumeric(
            node: LiteralNode::createFromNativeType($variable->computedValue),
        );

        if ($numericValue === null) {
            return null;
        }

        $value = LiteralNode::createFromNativeType(
            value: $numericValue - 1,
        );

        $variable->mutate($scope, $value);

        return $value;
    }

    public function reduceDecrementPost(
        ScopeInterface $scope,
        ExpressionNodeInterface $expression,
    ): ?ExpressionNodeInterface {
        if (!$expression instanceof IdentifierNode) {
            return null;
        }

        $variable = $scope->get($expression);

        if (!$variable->hasComputedValue()) {
            return null;
        }

        $computedValue = LiteralNode::createFromNativeType($variable->computedValue);
        $numericValue = $this->castNodeToNumeric($computedValue);

        if ($numericValue === null) {
            return null;
        }

        $value = LiteralNode::createFromNativeType(
            value: $numericValue - 1,
        );

        $variable->mutate($scope, $value);

        return $computedValue;
    }
}
