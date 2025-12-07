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
use Tuxxedo\View\Lumi\Syntax\Node\UnaryOpNode;

interface ExpressionReducerInterface
{
    public function reduceBinaryOp(
        ScopeInterface $scope,
        BinaryOpNode $node,
    ): ?ExpressionNodeInterface;

    public function reduceUnaryOp(
        ScopeInterface $scope,
        UnaryOpNode $node,
    ): ?ExpressionNodeInterface;

    public function reduceAssignment(
        ScopeInterface $scope,
        AssignmentNode $node,
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

    public function reduceBitwiseNot(
        ScopeInterface $scope,
        ExpressionNodeInterface $expression,
    ): ?ExpressionNodeInterface;
}
