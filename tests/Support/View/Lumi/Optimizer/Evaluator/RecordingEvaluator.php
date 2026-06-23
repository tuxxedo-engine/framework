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

namespace Support\View\Lumi\Optimizer\Evaluator;

use Tuxxedo\View\Lumi\Optimizer\Evaluator\EvaluatorInterface;
use Tuxxedo\View\Lumi\Optimizer\Evaluator\EvaluatorResult;
use Tuxxedo\View\Lumi\Optimizer\Scope\ScopeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\AssignmentNode;
use Tuxxedo\View\Lumi\Syntax\Node\BinaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Node\ExpressionNodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\GroupNode;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Node\UnaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Type;

class RecordingEvaluator implements EvaluatorInterface
{
    public ?ScopeInterface $lastBinaryOpScope = null;
    public ?BinaryOpNode $lastBinaryOpNode = null;

    public ?ScopeInterface $lastUnaryOpScope = null;
    public ?UnaryOpNode $lastUnaryOpNode = null;

    public ?ScopeInterface $lastAssignmentScope = null;
    public ?AssignmentNode $lastAssignmentNode = null;

    public function __construct(
        private readonly EvaluatorInterface $evaluator,
        public ?ExpressionNodeInterface $binaryOpReturn = null,
        public ?ExpressionNodeInterface $unaryOpReturn = null,
        public ?ExpressionNodeInterface $assignmentReturn = null,
    ) {
        $this->binaryOpReturn ??= LiteralNode::createString('/* binary */');
        $this->unaryOpReturn ??= LiteralNode::createString('/* unary */');
        $this->assignmentReturn ??= LiteralNode::createString('/* assignment */');
    }

    public function castValue(
        Type $type,
        string $value,
    ): string|int|float|bool|null {
        return $this->evaluator->castValue($type, $value);
    }

    public function castNode(
        Type $type,
        LiteralNode $node,
    ): LiteralNode {
        return $this->evaluator->castNode($type, $node);
    }

    public function castNodeToValue(
        LiteralNode $node,
    ): string|int|float|bool|null {
        return $this->evaluator->castNodeToValue($node);
    }

    public function castNodeToNumeric(
        LiteralNode $node,
    ): int|float|null {
        return $this->evaluator->castNodeToNumeric($node);
    }

    public function toString(
        LiteralNode $node,
    ): string {
        return $this->evaluator->toString($node);
    }

    public function toInt(
        LiteralNode $node,
    ): int {
        return $this->evaluator->toInt($node);
    }

    public function toFloat(
        LiteralNode $node,
    ): float {
        return $this->evaluator->toFloat($node);
    }

    public function toBool(
        LiteralNode $node,
    ): bool {
        return $this->evaluator->toBool($node);
    }

    public function isTrue(
        ScopeInterface $scope,
        LiteralNode|IdentifierNode $node,
    ): EvaluatorResult {
        return $this->evaluator->isTrue($scope, $node);
    }

    public function isFalse(
        ScopeInterface $scope,
        LiteralNode|IdentifierNode $node,
    ): EvaluatorResult {
        return $this->evaluator->isFalse($scope, $node);
    }

    public function isTruthy(
        ScopeInterface $scope,
        LiteralNode|IdentifierNode $node,
    ): bool {
        return $this->evaluator->isTruthy($scope, $node);
    }

    public function isFalsy(
        ScopeInterface $scope,
        LiteralNode|IdentifierNode $node,
    ): bool {
        return $this->evaluator->isFalsy($scope, $node);
    }

    public function isNull(
        ScopeInterface $scope,
        LiteralNode|IdentifierNode $node,
    ): bool {
        return $this->evaluator->isNull($scope, $node);
    }

    public function checkExpression(
        ScopeInterface $scope,
        ExpressionNodeInterface $node,
    ): EvaluatorResult {
        return $this->evaluator->checkExpression($scope, $node);
    }

    public function checkLiteral(
        LiteralNode $node,
    ): EvaluatorResult {
        return $this->evaluator->checkLiteral($node);
    }

    public function checkIdentifier(
        ScopeInterface $scope,
        IdentifierNode $node,
    ): EvaluatorResult {
        return $this->evaluator->checkIdentifier($scope, $node);
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
        $this->lastBinaryOpScope = $scope;
        $this->lastBinaryOpNode = $node;

        return $this->binaryOpReturn;
    }

    public function unaryOp(
        ScopeInterface $scope,
        UnaryOpNode $node,
    ): ?ExpressionNodeInterface {
        $this->lastUnaryOpScope = $scope;
        $this->lastUnaryOpNode = $node;

        return $this->unaryOpReturn;
    }

    public function assignment(
        ScopeInterface $scope,
        AssignmentNode $node,
    ): ?ExpressionNodeInterface {
        $this->lastAssignmentScope = $scope;
        $this->lastAssignmentNode = $node;

        return $this->assignmentReturn;
    }

    public function dereference(
        ScopeInterface $scope,
        ExpressionNodeInterface $node,
    ): ?ExpressionNodeInterface {
        return $this->evaluator->dereference($scope, $node);
    }

    public function dereferenceGroup(
        GroupNode $node,
    ): ExpressionNodeInterface {
        return $this->evaluator->dereferenceGroup($node);
    }

    public function dereferenceIdentifier(
        ScopeInterface $scope,
        IdentifierNode $node,
    ): ?ExpressionNodeInterface {
        return $this->evaluator->dereferenceIdentifier($scope, $node);
    }

    public function reduceConcat(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface {
        return null;
    }

    public function reduceAdd(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface {
        return null;
    }

    public function reduceSubtract(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface {
        return null;
    }

    public function reduceMultiply(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface {
        return null;
    }

    public function reduceDivide(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface {
        return null;
    }

    public function reduceModulus(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface {
        return null;
    }

    public function reduceEqual(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface {
        return null;
    }

    public function reduceNotEqual(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface {
        return null;
    }

    public function reduceGreater(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface {
        return null;
    }

    public function reduceLess(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface {
        return null;
    }

    public function reduceGreaterEqual(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface {
        return null;
    }

    public function reduceLessEqual(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface {
        return null;
    }

    public function reduceAnd(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface {
        return null;
    }

    public function reduceOr(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface {
        return null;
    }

    public function reduceXor(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface {
        return null;
    }

    public function reduceExponentiate(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface {
        return null;
    }

    public function reduceBitwiseAnd(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface {
        return null;
    }

    public function reduceBitwiseOr(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface {
        return null;
    }

    public function reduceBitwiseXor(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface {
        return null;
    }

    public function reduceBitwiseShiftLeft(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface {
        return null;
    }

    public function reduceBitwiseShiftRight(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface {
        return null;
    }

    public function reduceNullCoalesce(
        ScopeInterface $scope,
        ExpressionNodeInterface $left,
        ExpressionNodeInterface $right,
    ): ?ExpressionNodeInterface {
        return null;
    }

    public function reduceNot(
        ScopeInterface $scope,
        ExpressionNodeInterface $expression,
    ): ?ExpressionNodeInterface {
        return null;
    }

    public function reduceNegate(
        ScopeInterface $scope,
        ExpressionNodeInterface $expression,
    ): ?ExpressionNodeInterface {
        return null;
    }

    public function reduceBitwiseNot(
        ScopeInterface $scope,
        ExpressionNodeInterface $expression,
    ): ?ExpressionNodeInterface {
        return null;
    }

    public function reduceIncrementPre(
        ScopeInterface $scope,
        ExpressionNodeInterface $expression,
    ): ?ExpressionNodeInterface {
        return null;
    }

    public function reduceIncrementPost(
        ScopeInterface $scope,
        ExpressionNodeInterface $expression,
    ): ?ExpressionNodeInterface {
        return null;
    }

    public function reduceDecrementPre(
        ScopeInterface $scope,
        ExpressionNodeInterface $expression,
    ): ?ExpressionNodeInterface {
        return null;
    }

    public function reduceDecrementPost(
        ScopeInterface $scope,
        ExpressionNodeInterface $expression,
    ): ?ExpressionNodeInterface {
        return null;
    }
}
