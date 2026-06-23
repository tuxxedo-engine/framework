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

use Tuxxedo\View\Lumi\Optimizer\Scope\ScopeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\AssignmentNode;
use Tuxxedo\View\Lumi\Syntax\Node\BinaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Node\ExpressionNodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\GroupNode;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Node\UnaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Type;

interface EvaluatorInterface
{
    public function castValue(
        Type $type,
        string $value,
    ): string|int|float|bool|null;

    public function castNode(
        Type $type,
        LiteralNode $node,
    ): LiteralNode;

    public function castNodeToValue(
        LiteralNode $node,
    ): string|int|float|bool|null;

    public function castNodeToNumeric(
        LiteralNode $node,
    ): int|float|null;

    public function toString(
        LiteralNode $node,
    ): string;

    public function toInt(
        LiteralNode $node,
    ): int;

    public function toFloat(
        LiteralNode $node,
    ): float;

    public function toBool(
        LiteralNode $node,
    ): bool;

    public function isTrue(
        ScopeInterface $scope,
        LiteralNode|IdentifierNode $node,
    ): EvaluatorResult;

    public function isFalse(
        ScopeInterface $scope,
        LiteralNode|IdentifierNode $node,
    ): EvaluatorResult;

    public function isTruthy(
        ScopeInterface $scope,
        LiteralNode|IdentifierNode $node,
    ): bool;

    public function isFalsy(
        ScopeInterface $scope,
        LiteralNode|IdentifierNode $node,
    ): bool;

    public function isNull(
        ScopeInterface $scope,
        LiteralNode|IdentifierNode $node,
    ): bool;

    public function checkExpression(
        ScopeInterface $scope,
        ExpressionNodeInterface $node,
    ): EvaluatorResult;

    public function checkLiteral(
        LiteralNode $node,
    ): EvaluatorResult;

    public function checkIdentifier(
        ScopeInterface $scope,
        IdentifierNode $node,
    ): EvaluatorResult;

    public function expression(
        ScopeInterface $scope,
        ExpressionNodeInterface $node,
    ): ?ExpressionNodeInterface;

    public function binaryOp(
        ScopeInterface $scope,
        BinaryOpNode $node,
    ): ?ExpressionNodeInterface;

    public function unaryOp(
        ScopeInterface $scope,
        UnaryOpNode $node,
    ): ?ExpressionNodeInterface;

    public function assignment(
        ScopeInterface $scope,
        AssignmentNode $node,
    ): ?ExpressionNodeInterface;

    public function dereference(
        ScopeInterface $scope,
        ExpressionNodeInterface $node,
    ): ?ExpressionNodeInterface;

    public function dereferenceGroup(
        GroupNode $node,
    ): ExpressionNodeInterface;

    public function dereferenceIdentifier(
        ScopeInterface $scope,
        IdentifierNode $node,
    ): ?ExpressionNodeInterface;

    public function reduceConcat(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface;

    public function reduceAdd(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface;

    public function reduceSubtract(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface;

    public function reduceMultiply(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface;

    public function reduceDivide(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface;

    public function reduceModulus(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface;

    public function reduceEqual(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface;

    public function reduceNotEqual(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface;

    public function reduceGreater(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface;

    public function reduceLess(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface;

    public function reduceGreaterEqual(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface;

    public function reduceLessEqual(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface;

    public function reduceAnd(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface;

    public function reduceOr(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface;

    public function reduceXor(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface;

    public function reduceExponentiate(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface;

    public function reduceBitwiseAnd(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface;

    public function reduceBitwiseOr(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface;

    public function reduceBitwiseXor(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface;

    public function reduceBitwiseShiftLeft(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface;

    public function reduceBitwiseShiftRight(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface;

    public function reduceNullCoalesce(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface;

    public function reduceNot(
        ScopeInterface $scope,
        ExpressionNodeInterface $expression,
    ): ?ExpressionNodeInterface;

    public function reduceNegate(
        ScopeInterface $scope,
        ExpressionNodeInterface $expression,
    ): ?ExpressionNodeInterface;

    public function reduceBitwiseNot(
        ScopeInterface $scope,
        ExpressionNodeInterface $expression,
    ): ?ExpressionNodeInterface;

    public function reduceIncrementPre(
        ScopeInterface $scope,
        ExpressionNodeInterface $expression,
    ): ?ExpressionNodeInterface;

    public function reduceIncrementPost(
        ScopeInterface $scope,
        ExpressionNodeInterface $expression,
    ): ?ExpressionNodeInterface;

    public function reduceDecrementPre(
        ScopeInterface $scope,
        ExpressionNodeInterface $expression,
    ): ?ExpressionNodeInterface;

    public function reduceDecrementPost(
        ScopeInterface $scope,
        ExpressionNodeInterface $expression,
    ): ?ExpressionNodeInterface;
}
