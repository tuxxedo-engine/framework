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

use Tuxxedo\View\Lumi\Optimizer\Scope\ScopeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\AssignmentNode;
use Tuxxedo\View\Lumi\Syntax\Node\BinaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Node\ExpressionNodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Node\UnaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Operator\AssignmentSymbol;
use Tuxxedo\View\Lumi\Syntax\Operator\BinarySymbol;
use Tuxxedo\View\Lumi\Syntax\Operator\UnarySymbol;
use Tuxxedo\View\Lumi\Syntax\Type;

class ExpressionReducer implements ExpressionReducerInterface
{
    public function __construct(
        private readonly EvaluatorInterface $evaluator,
    ) {
    }

    private function resolve(
        ScopeInterface $scope,
        ExpressionNodeInterface $node,
    ): ?ExpressionNodeInterface {
        if ($node instanceof IdentifierNode) {
            $variable = $scope->get($node);

            if ($variable->hasComputedValue()) {
                return LiteralNode::createFromNativeType($variable->computedValue);
            }
        }

        return $this->evaluator->dereference($scope, $node);
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

        $local = $this->evaluator->castNodeToNumeric($node);

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

        return $this->evaluator->toBool($node);
    }

    public function reduceBinaryOp(
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

    public function reduceUnaryOp(
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

    public function reduceAssignment(
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
            value: $this->evaluator->castNodeToValue($left) . $this->evaluator->castNodeToValue($right),
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

        if (\intval($rightValue) === 0) {
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
            return LiteralNode::createBool(!\boolval($this->evaluator->castNodeToValue($expression)));
        }

        return null;
    }

    public function reduceNegate(
        ScopeInterface $scope,
        ExpressionNodeInterface $expression,
    ): ?ExpressionNodeInterface {
        // @todo Implement

        return null;
    }

    public function reduceBitwiseNot(
        ScopeInterface $scope,
        ExpressionNodeInterface $expression,
    ): ?ExpressionNodeInterface {
        $expression = $this->resolve($scope, $expression);

        if ($expression instanceof LiteralNode) {
            return LiteralNode::createInt(~((int) $this->evaluator->castNodeToValue($expression)));
        }

        return null;
    }

    public function reduceIncrementPre(
        ScopeInterface $scope,
        ExpressionNodeInterface $expression,
    ): ?ExpressionNodeInterface {
        // @todo Implement

        return null;
    }

    public function reduceIncrementPost(
        ScopeInterface $scope,
        ExpressionNodeInterface $expression,
    ): ?ExpressionNodeInterface {
        // @todo Implement

        return null;
    }

    public function reduceDecrementPre(
        ScopeInterface $scope,
        ExpressionNodeInterface $expression,
    ): ?ExpressionNodeInterface {
        // @todo Implement

        return null;
    }

    public function reduceDecrementPost(
        ScopeInterface $scope,
        ExpressionNodeInterface $expression,
    ): ?ExpressionNodeInterface {
        // @todo Implement

        return null;
    }
}
