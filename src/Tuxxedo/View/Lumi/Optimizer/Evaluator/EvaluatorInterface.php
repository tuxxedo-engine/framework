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
use Tuxxedo\View\Lumi\Syntax\Node\ExpressionNodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Type;

// @todo Support BinaryOp
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

    public function evaluateExpression(
        ScopeInterface $scope,
        ExpressionNodeInterface $node,
    ): EvaluatorResult;

    public function evaluateLiteral(
        LiteralNode $node,
    ): EvaluatorResult;

    public function evaluateIdentifier(
        ScopeInterface $scope,
        IdentifierNode $node,
    ): EvaluatorResult;
}
