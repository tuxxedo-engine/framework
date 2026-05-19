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

namespace Unit\View\Lumi\Optimizer\Evaluator;

use PHPUnit\Framework\TestCase;
use Support\View\Lumi\Optimizer\Evaluator\RecordingExpressionReducer;
use Tuxxedo\View\Lumi\Optimizer\Evaluator\Evaluator;
use Tuxxedo\View\Lumi\Optimizer\Evaluator\EvaluatorResult;
use Tuxxedo\View\Lumi\Optimizer\Scope\Scope;
use Tuxxedo\View\Lumi\Syntax\Node\AssignmentNode;
use Tuxxedo\View\Lumi\Syntax\Node\BinaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Node\GroupNode;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Node\UnaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Operator\AssignmentSymbol;
use Tuxxedo\View\Lumi\Syntax\Operator\BinarySymbol;
use Tuxxedo\View\Lumi\Syntax\Operator\UnarySymbol;
use Tuxxedo\View\Lumi\Syntax\Type;

class EvaluatorTest extends TestCase
{
    private Evaluator $evaluator;
    private Scope $scope;

    protected function setUp(): void
    {
        $this->evaluator = new Evaluator();
        $this->scope = new Scope(
            evaluator: $this->evaluator,
        );
    }

    private function assign(
        string $name,
        LiteralNode|IdentifierNode|BinaryOpNode $value,
    ): void {
        $this->scope->assign(
            node: new AssignmentNode(
                name: new IdentifierNode(
                    name: $name,
                ),
                value: $value,
                operator: AssignmentSymbol::ASSIGN,
            ),
        );
    }

    public function testCastValueWithStringReturnsOperandVerbatim(): void
    {
        self::assertSame(
            'hello',
            $this->evaluator->castValue(
                type: Type::STRING,
                value: 'hello',
            ),
        );
    }

    public function testCastValueWithIntReturnsParsedInt(): void
    {
        self::assertSame(
            42,
            $this->evaluator->castValue(
                type: Type::INT,
                value: '42',
            ),
        );
    }

    public function testCastValueWithIntForNonNumericReturnsZero(): void
    {
        self::assertSame(
            0,
            $this->evaluator->castValue(
                type: Type::INT,
                value: 'abc',
            ),
        );
    }

    public function testCastValueWithFloatReturnsParsedFloat(): void
    {
        self::assertSame(
            3.14,
            $this->evaluator->castValue(
                type: Type::FLOAT,
                value: '3.14',
            ),
        );
    }

    public function testCastValueWithBoolTrueLiteralReturnsTrue(): void
    {
        self::assertTrue(
            $this->evaluator->castValue(
                type: Type::BOOL,
                value: 'true',
            ),
        );
    }

    public function testCastValueWithBoolNonTrueLiteralReturnsFalse(): void
    {
        self::assertFalse(
            $this->evaluator->castValue(
                type: Type::BOOL,
                value: 'false',
            ),
        );
    }

    public function testCastValueWithNullReturnsNullRegardlessOfOperand(): void
    {
        self::assertNull(
            $this->evaluator->castValue(
                type: Type::NULL,
                value: 'whatever',
            ),
        );
    }

    public function testCastNodeReturnsSameInstanceWhenTypeMatches(): void
    {
        $node = LiteralNode::createInt(7);

        self::assertSame(
            $node,
            $this->evaluator->castNode(
                type: Type::INT,
                node: $node,
            ),
        );
    }

    public function testCastNodeStringToIntProducesIntLiteral(): void
    {
        $result = $this->evaluator->castNode(
            type: Type::INT,
            node: LiteralNode::createString('42'),
        );

        self::assertSame('42', $result->operand);
        self::assertSame(Type::INT, $result->type);
    }

    public function testCastNodeIntToStringProducesStringLiteral(): void
    {
        $result = $this->evaluator->castNode(
            type: Type::STRING,
            node: LiteralNode::createInt(42),
        );

        self::assertSame('42', $result->operand);
        self::assertSame(Type::STRING, $result->type);
    }

    public function testCastNodeNonZeroIntToBoolProducesTrueLiteral(): void
    {
        $result = $this->evaluator->castNode(
            type: Type::BOOL,
            node: LiteralNode::createInt(1),
        );

        self::assertSame('true', $result->operand);
        self::assertSame(Type::BOOL, $result->type);
    }

    public function testCastNodeZeroIntToBoolProducesFalseLiteral(): void
    {
        $result = $this->evaluator->castNode(
            type: Type::BOOL,
            node: LiteralNode::createInt(0),
        );

        self::assertSame('false', $result->operand);
        self::assertSame(Type::BOOL, $result->type);
    }

    public function testCastNodeStringTrueToBoolProducesTrueLiteral(): void
    {
        $result = $this->evaluator->castNode(
            type: Type::BOOL,
            node: LiteralNode::createString('true'),
        );

        self::assertSame('true', $result->operand);
        self::assertSame(Type::BOOL, $result->type);
    }

    public function testCastNodeToNullProducesNullLiteral(): void
    {
        $result = $this->evaluator->castNode(
            type: Type::NULL,
            node: LiteralNode::createInt(99),
        );

        self::assertSame('null', $result->operand);
        self::assertSame(Type::NULL, $result->type);
    }

    public function testCastNodeFloatToIntTruncates(): void
    {
        $result = $this->evaluator->castNode(
            type: Type::INT,
            node: LiteralNode::createFloat(3.7),
        );

        self::assertSame('3', $result->operand);
        self::assertSame(Type::INT, $result->type);
    }

    public function testCastNodeToValueDelegatesToCastValue(): void
    {
        self::assertSame(
            42,
            $this->evaluator->castNodeToValue(
                node: LiteralNode::createInt(42),
            ),
        );
    }

    public function testCastNodeToNumericIntPassesThrough(): void
    {
        self::assertSame(
            5,
            $this->evaluator->castNodeToNumeric(
                node: LiteralNode::createInt(5),
            ),
        );
    }

    public function testCastNodeToNumericFloatPassesThrough(): void
    {
        self::assertSame(
            3.14,
            $this->evaluator->castNodeToNumeric(
                node: LiteralNode::createFloat(3.14),
            ),
        );
    }

    public function testCastNodeToNumericStringWithIntegerReturnsInt(): void
    {
        self::assertSame(
            42,
            $this->evaluator->castNodeToNumeric(
                node: LiteralNode::createString('42'),
            ),
        );
    }

    public function testCastNodeToNumericStringWithFloatReturnsFloat(): void
    {
        self::assertSame(
            3.14,
            $this->evaluator->castNodeToNumeric(
                node: LiteralNode::createString('3.14'),
            ),
        );
    }

    public function testCastNodeToNumericStringWithNonNumericReturnsNull(): void
    {
        self::assertNull(
            $this->evaluator->castNodeToNumeric(
                node: LiteralNode::createString('abc'),
            ),
        );
    }

    public function testCastNodeToNumericBoolTrueReturnsOne(): void
    {
        self::assertSame(
            1,
            $this->evaluator->castNodeToNumeric(
                node: LiteralNode::createBool(true),
            ),
        );
    }

    public function testCastNodeToNumericBoolFalseReturnsZero(): void
    {
        self::assertSame(
            0,
            $this->evaluator->castNodeToNumeric(
                node: LiteralNode::createBool(false),
            ),
        );
    }

    public function testCastNodeToNumericNullReturnsZero(): void
    {
        self::assertSame(
            0,
            $this->evaluator->castNodeToNumeric(
                node: LiteralNode::createNull(),
            ),
        );
    }

    public function testToStringWithStringReturnsOperandUnchanged(): void
    {
        self::assertSame(
            'hello',
            $this->evaluator->toString(
                node: LiteralNode::createString('hello'),
            ),
        );
    }

    public function testToStringWithIntReturnsOperandVerbatim(): void
    {
        self::assertSame(
            '42',
            $this->evaluator->toString(
                node: LiteralNode::createInt(42),
            ),
        );
    }

    public function testToIntCastsOperandToInt(): void
    {
        self::assertSame(
            42,
            $this->evaluator->toInt(
                node: LiteralNode::createString('42'),
            ),
        );
    }

    public function testToFloatCastsOperandToFloat(): void
    {
        self::assertSame(
            3.14,
            $this->evaluator->toFloat(
                node: LiteralNode::createString('3.14'),
            ),
        );
    }

    public function testToBoolWithBoolTrueLiteralReturnsTrue(): void
    {
        self::assertTrue(
            $this->evaluator->toBool(
                node: LiteralNode::createBool(true),
            ),
        );
    }

    public function testToBoolWithBoolFalseLiteralReturnsFalse(): void
    {
        self::assertFalse(
            $this->evaluator->toBool(
                node: LiteralNode::createBool(false),
            ),
        );
    }

    public function testToBoolWithNullLiteralReturnsFalse(): void
    {
        self::assertFalse(
            $this->evaluator->toBool(
                node: LiteralNode::createNull(),
            ),
        );
    }

    public function testToBoolWithStringTrueOperandReturnsTrue(): void
    {
        self::assertTrue(
            $this->evaluator->toBool(
                node: LiteralNode::createString('true'),
            ),
        );
    }

    public function testToBoolWithNonZeroIntReturnsTrue(): void
    {
        self::assertTrue(
            $this->evaluator->toBool(
                node: LiteralNode::createInt(1),
            ),
        );
    }

    public function testToBoolWithZeroIntReturnsFalse(): void
    {
        self::assertFalse(
            $this->evaluator->toBool(
                node: LiteralNode::createInt(0),
            ),
        );
    }

    public function testToBoolWithEmptyStringReturnsFalse(): void
    {
        self::assertFalse(
            $this->evaluator->toBool(
                node: LiteralNode::createString(''),
            ),
        );
    }

    public function testIsTrueWithBoolTrueLiteralReturnsIsTrue(): void
    {
        self::assertSame(
            EvaluatorResult::IS_TRUE,
            $this->evaluator->isTrue(
                scope: $this->scope,
                node: LiteralNode::createBool(true),
            ),
        );
    }

    public function testIsTrueWithBoolFalseLiteralReturnsIsFalse(): void
    {
        self::assertSame(
            EvaluatorResult::IS_FALSE,
            $this->evaluator->isTrue(
                scope: $this->scope,
                node: LiteralNode::createBool(false),
            ),
        );
    }

    public function testIsTrueWithNonBoolLiteralReturnsIsFalse(): void
    {
        self::assertSame(
            EvaluatorResult::IS_FALSE,
            $this->evaluator->isTrue(
                scope: $this->scope,
                node: LiteralNode::createInt(1),
            ),
        );
    }

    public function testIsTrueResolvesIdentifierThroughScope(): void
    {
        $this->assign(
            name: 'flag',
            value: LiteralNode::createBool(true),
        );

        self::assertSame(
            EvaluatorResult::IS_TRUE,
            $this->evaluator->isTrue(
                scope: $this->scope,
                node: new IdentifierNode(
                    name: 'flag',
                ),
            ),
        );
    }

    public function testIsTrueWithUndefinedIdentifierReturnsUnknown(): void
    {
        self::assertSame(
            EvaluatorResult::UNKNOWN,
            $this->evaluator->isTrue(
                scope: $this->scope,
                node: new IdentifierNode(
                    name: 'missing',
                ),
            ),
        );
    }

    public function testIsFalseWithBoolFalseLiteralReturnsIsTrue(): void
    {
        self::assertSame(
            EvaluatorResult::IS_TRUE,
            $this->evaluator->isFalse(
                scope: $this->scope,
                node: LiteralNode::createBool(false),
            ),
        );
    }

    public function testIsFalseWithBoolTrueLiteralReturnsIsFalse(): void
    {
        self::assertSame(
            EvaluatorResult::IS_FALSE,
            $this->evaluator->isFalse(
                scope: $this->scope,
                node: LiteralNode::createBool(true),
            ),
        );
    }

    public function testIsFalseResolvesIdentifierThroughScope(): void
    {
        $this->assign(
            name: 'flag',
            value: LiteralNode::createBool(false),
        );

        self::assertSame(
            EvaluatorResult::IS_TRUE,
            $this->evaluator->isFalse(
                scope: $this->scope,
                node: new IdentifierNode(
                    name: 'flag',
                ),
            ),
        );
    }

    public function testIsFalseWithUndefinedIdentifierReturnsUnknown(): void
    {
        self::assertSame(
            EvaluatorResult::UNKNOWN,
            $this->evaluator->isFalse(
                scope: $this->scope,
                node: new IdentifierNode(
                    name: 'missing',
                ),
            ),
        );
    }

    public function testIsTruthyWithBoolTrueLiteralReturnsTrue(): void
    {
        self::assertTrue(
            $this->evaluator->isTruthy(
                scope: $this->scope,
                node: LiteralNode::createBool(true),
            ),
        );
    }

    public function testIsTruthyWithBoolFalseLiteralReturnsFalse(): void
    {
        self::assertFalse(
            $this->evaluator->isTruthy(
                scope: $this->scope,
                node: LiteralNode::createBool(false),
            ),
        );
    }

    public function testIsTruthyWithStringTrueLiteralReturnsTrue(): void
    {
        self::assertTrue(
            $this->evaluator->isTruthy(
                scope: $this->scope,
                node: LiteralNode::createString('true'),
            ),
        );
    }

    public function testIsTruthyWithNonZeroIntReturnsTrue(): void
    {
        self::assertTrue(
            $this->evaluator->isTruthy(
                scope: $this->scope,
                node: LiteralNode::createInt(1),
            ),
        );
    }

    public function testIsTruthyWithZeroIntReturnsFalse(): void
    {
        self::assertFalse(
            $this->evaluator->isTruthy(
                scope: $this->scope,
                node: LiteralNode::createInt(0),
            ),
        );
    }

    public function testIsTruthyResolvesIdentifierThroughScope(): void
    {
        $this->assign(
            name: 'flag',
            value: LiteralNode::createBool(true),
        );

        self::assertTrue(
            $this->evaluator->isTruthy(
                scope: $this->scope,
                node: new IdentifierNode(
                    name: 'flag',
                ),
            ),
        );
    }

    public function testIsTruthyWithUndefinedIdentifierReturnsFalse(): void
    {
        self::assertFalse(
            $this->evaluator->isTruthy(
                scope: $this->scope,
                node: new IdentifierNode(
                    name: 'missing',
                ),
            ),
        );
    }

    public function testIsFalsyWithBoolFalseLiteralReturnsTrue(): void
    {
        self::assertTrue(
            $this->evaluator->isFalsy(
                scope: $this->scope,
                node: LiteralNode::createBool(false),
            ),
        );
    }

    public function testIsFalsyWithBoolTrueLiteralReturnsFalse(): void
    {
        self::assertFalse(
            $this->evaluator->isFalsy(
                scope: $this->scope,
                node: LiteralNode::createBool(true),
            ),
        );
    }

    public function testIsFalsyWithNullLiteralReturnsTrue(): void
    {
        self::assertTrue(
            $this->evaluator->isFalsy(
                scope: $this->scope,
                node: LiteralNode::createNull(),
            ),
        );
    }

    public function testIsFalsyResolvesIdentifierThroughScope(): void
    {
        $this->assign(
            name: 'flag',
            value: LiteralNode::createBool(false),
        );

        self::assertTrue(
            $this->evaluator->isFalsy(
                scope: $this->scope,
                node: new IdentifierNode(
                    name: 'flag',
                ),
            ),
        );
    }

    public function testIsFalsyWithUndefinedIdentifierReturnsFalse(): void
    {
        self::assertFalse(
            $this->evaluator->isFalsy(
                scope: $this->scope,
                node: new IdentifierNode(
                    name: 'missing',
                ),
            ),
        );
    }

    public function testIsNullWithNullLiteralReturnsTrue(): void
    {
        self::assertTrue(
            $this->evaluator->isNull(
                scope: $this->scope,
                node: LiteralNode::createNull(),
            ),
        );
    }

    public function testIsNullWithNonNullLiteralReturnsFalse(): void
    {
        self::assertFalse(
            $this->evaluator->isNull(
                scope: $this->scope,
                node: LiteralNode::createInt(0),
            ),
        );
    }

    public function testIsNullResolvesIdentifierThroughScope(): void
    {
        $this->assign(
            name: 'value',
            value: LiteralNode::createNull(),
        );

        self::assertTrue(
            $this->evaluator->isNull(
                scope: $this->scope,
                node: new IdentifierNode(
                    name: 'value',
                ),
            ),
        );
    }

    public function testIsNullWithUndefinedIdentifierReturnsFalse(): void
    {
        self::assertFalse(
            $this->evaluator->isNull(
                scope: $this->scope,
                node: new IdentifierNode(
                    name: 'missing',
                ),
            ),
        );
    }

    public function testCheckLiteralWithNullReturnsIsFalse(): void
    {
        self::assertSame(
            EvaluatorResult::IS_FALSE,
            $this->evaluator->checkLiteral(
                node: LiteralNode::createNull(),
            ),
        );
    }

    public function testCheckLiteralWithBoolTrueReturnsIsTrue(): void
    {
        self::assertSame(
            EvaluatorResult::IS_TRUE,
            $this->evaluator->checkLiteral(
                node: LiteralNode::createBool(true),
            ),
        );
    }

    public function testCheckLiteralWithBoolFalseReturnsIsFalse(): void
    {
        self::assertSame(
            EvaluatorResult::IS_FALSE,
            $this->evaluator->checkLiteral(
                node: LiteralNode::createBool(false),
            ),
        );
    }

    public function testCheckLiteralWithStringTrueReturnsIsTrue(): void
    {
        self::assertSame(
            EvaluatorResult::IS_TRUE,
            $this->evaluator->checkLiteral(
                node: LiteralNode::createString('true'),
            ),
        );
    }

    public function testCheckLiteralWithNonEmptyStringReturnsIsTrue(): void
    {
        self::assertSame(
            EvaluatorResult::IS_TRUE,
            $this->evaluator->checkLiteral(
                node: LiteralNode::createString('hello'),
            ),
        );
    }

    public function testCheckLiteralWithEmptyStringReturnsIsFalse(): void
    {
        self::assertSame(
            EvaluatorResult::IS_FALSE,
            $this->evaluator->checkLiteral(
                node: LiteralNode::createString(''),
            ),
        );
    }

    public function testCheckLiteralWithNonZeroIntReturnsIsTrue(): void
    {
        self::assertSame(
            EvaluatorResult::IS_TRUE,
            $this->evaluator->checkLiteral(
                node: LiteralNode::createInt(1),
            ),
        );
    }

    public function testCheckLiteralWithZeroIntReturnsIsFalse(): void
    {
        self::assertSame(
            EvaluatorResult::IS_FALSE,
            $this->evaluator->checkLiteral(
                node: LiteralNode::createInt(0),
            ),
        );
    }

    public function testCheckIdentifierWithUndefinedReturnsUnknown(): void
    {
        self::assertSame(
            EvaluatorResult::UNKNOWN,
            $this->evaluator->checkIdentifier(
                scope: $this->scope,
                node: new IdentifierNode(
                    name: 'missing',
                ),
            ),
        );
    }

    public function testCheckIdentifierWithLiteralBoolTrueReturnsIsTrue(): void
    {
        $this->assign(
            name: 'flag',
            value: LiteralNode::createBool(true),
        );

        self::assertSame(
            EvaluatorResult::IS_TRUE,
            $this->evaluator->checkIdentifier(
                scope: $this->scope,
                node: new IdentifierNode(
                    name: 'flag',
                ),
            ),
        );
    }

    public function testCheckIdentifierWithLiteralBoolFalseReturnsIsFalse(): void
    {
        $this->assign(
            name: 'flag',
            value: LiteralNode::createBool(false),
        );

        self::assertSame(
            EvaluatorResult::IS_FALSE,
            $this->evaluator->checkIdentifier(
                scope: $this->scope,
                node: new IdentifierNode(
                    name: 'flag',
                ),
            ),
        );
    }

    public function testCheckIdentifierUsesComputedValueWhenValueIsNotLiteral(): void
    {
        $this->assign(
            name: 'inner',
            value: LiteralNode::createInt(7),
        );
        $this->assign(
            name: 'outer',
            value: new IdentifierNode(
                name: 'inner',
            ),
        );

        self::assertSame(
            EvaluatorResult::IS_TRUE,
            $this->evaluator->checkIdentifier(
                scope: $this->scope,
                node: new IdentifierNode(
                    name: 'outer',
                ),
            ),
        );
    }

    public function testCheckExpressionWithLiteralRoutesToCheckLiteral(): void
    {
        self::assertSame(
            EvaluatorResult::IS_TRUE,
            $this->evaluator->checkExpression(
                scope: $this->scope,
                node: LiteralNode::createBool(true),
            ),
        );
    }

    public function testCheckExpressionWithIdentifierRoutesToCheckIdentifier(): void
    {
        $this->assign(
            name: 'flag',
            value: LiteralNode::createBool(true),
        );

        self::assertSame(
            EvaluatorResult::IS_TRUE,
            $this->evaluator->checkExpression(
                scope: $this->scope,
                node: new IdentifierNode(
                    name: 'flag',
                ),
            ),
        );
    }

    public function testCheckExpressionUnwrapsGroupOfLiteral(): void
    {
        self::assertSame(
            EvaluatorResult::IS_FALSE,
            $this->evaluator->checkExpression(
                scope: $this->scope,
                node: new GroupNode(
                    operand: LiteralNode::createBool(false),
                ),
            ),
        );
    }

    public function testCheckExpressionUnwrapsNestedGroupsOfLiteral(): void
    {
        self::assertSame(
            EvaluatorResult::IS_TRUE,
            $this->evaluator->checkExpression(
                scope: $this->scope,
                node: new GroupNode(
                    operand: new GroupNode(
                        operand: LiteralNode::createBool(true),
                    ),
                ),
            ),
        );
    }

    public function testCheckExpressionUnwrapsGroupOfIdentifier(): void
    {
        $this->assign(
            name: 'flag',
            value: LiteralNode::createBool(true),
        );

        self::assertSame(
            EvaluatorResult::IS_TRUE,
            $this->evaluator->checkExpression(
                scope: $this->scope,
                node: new GroupNode(
                    operand: new IdentifierNode(
                        name: 'flag',
                    ),
                ),
            ),
        );
    }

    public function testCheckExpressionWithBinaryOpReturnsUnknown(): void
    {
        self::assertSame(
            EvaluatorResult::UNKNOWN,
            $this->evaluator->checkExpression(
                scope: $this->scope,
                node: new BinaryOpNode(
                    left: LiteralNode::createInt(1),
                    right: LiteralNode::createInt(2),
                    operator: BinarySymbol::ADD,
                ),
            ),
        );
    }

    public function testCheckExpressionWithGroupOfBinaryOpReturnsUnknown(): void
    {
        self::assertSame(
            EvaluatorResult::UNKNOWN,
            $this->evaluator->checkExpression(
                scope: $this->scope,
                node: new GroupNode(
                    operand: new BinaryOpNode(
                        left: LiteralNode::createInt(1),
                        right: LiteralNode::createInt(2),
                        operator: BinarySymbol::ADD,
                    ),
                ),
            ),
        );
    }

    public function testExpressionWithLiteralReturnsSameLiteral(): void
    {
        $node = LiteralNode::createInt(5);

        self::assertSame(
            $node,
            $this->evaluator->expression(
                scope: $this->scope,
                node: $node,
            ),
        );
    }

    public function testExpressionWithIdentifierResolvesToBackingLiteral(): void
    {
        $value = LiteralNode::createInt(5);

        $this->assign(
            name: 'x',
            value: $value,
        );

        self::assertSame(
            $value,
            $this->evaluator->expression(
                scope: $this->scope,
                node: new IdentifierNode(
                    name: 'x',
                ),
            ),
        );
    }

    public function testExpressionWithUndefinedIdentifierReturnsNull(): void
    {
        self::assertNull(
            $this->evaluator->expression(
                scope: $this->scope,
                node: new IdentifierNode(
                    name: 'missing',
                ),
            ),
        );
    }

    public function testExpressionWithGroupOfLiteralUnwrapsToLiteral(): void
    {
        $literal = LiteralNode::createInt(5);

        self::assertSame(
            $literal,
            $this->evaluator->expression(
                scope: $this->scope,
                node: new GroupNode(
                    operand: $literal,
                ),
            ),
        );
    }

    public function testExpressionWithBinaryOpDelegatesToReducer(): void
    {
        $reducer = new RecordingExpressionReducer();
        $evaluator = new Evaluator(
            expressionReducer: $reducer,
        );

        $scope = new Scope(
            evaluator: $evaluator,
        );

        $node = new BinaryOpNode(
            left: LiteralNode::createInt(1),
            right: LiteralNode::createInt(2),
            operator: BinarySymbol::ADD,
        );

        $result = $evaluator->expression(
            scope: $scope,
            node: $node,
        );

        self::assertSame($node, $reducer->lastBinaryOpNode);
        self::assertSame($reducer->binaryOpReturn, $result);
    }

    public function testExpressionWithUnaryOpDelegatesToReducer(): void
    {
        $reducer = new RecordingExpressionReducer();
        $evaluator = new Evaluator(
            expressionReducer: $reducer,
        );

        $scope = new Scope(
            evaluator: $evaluator,
        );

        $node = new UnaryOpNode(
            operand: LiteralNode::createBool(true),
            operator: UnarySymbol::NOT,
        );

        $result = $evaluator->expression(
            scope: $scope,
            node: $node,
        );

        self::assertSame($node, $reducer->lastUnaryOpNode);
        self::assertSame($reducer->unaryOpReturn, $result);
    }

    public function testExpressionWithGroupOfBinaryOpDelegatesToReducer(): void
    {
        $reducer = new RecordingExpressionReducer();
        $evaluator = new Evaluator(
            expressionReducer: $reducer,
        );

        $scope = new Scope(
            evaluator: $evaluator,
        );

        $inner = new BinaryOpNode(
            left: LiteralNode::createInt(1),
            right: LiteralNode::createInt(2),
            operator: BinarySymbol::ADD,
        );

        $result = $evaluator->expression(
            scope: $scope,
            node: new GroupNode(
                operand: $inner,
            ),
        );

        self::assertSame($inner, $reducer->lastBinaryOpNode);
        self::assertSame($reducer->binaryOpReturn, $result);
    }

    public function testBinaryOpForwardsScopeAndNodeToReducer(): void
    {
        $reducer = new RecordingExpressionReducer();
        $evaluator = new Evaluator(
            expressionReducer: $reducer,
        );

        $scope = new Scope(
            evaluator: $evaluator,
        );

        $node = new BinaryOpNode(
            left: LiteralNode::createInt(1),
            right: LiteralNode::createInt(2),
            operator: BinarySymbol::ADD,
        );

        $result = $evaluator->binaryOp(
            scope: $scope,
            node: $node,
        );

        self::assertSame($scope, $reducer->lastBinaryOpScope);
        self::assertSame($node, $reducer->lastBinaryOpNode);
        self::assertSame($reducer->binaryOpReturn, $result);
    }

    public function testUnaryOpForwardsScopeAndNodeToReducer(): void
    {
        $reducer = new RecordingExpressionReducer();

        $evaluator = new Evaluator(
            expressionReducer: $reducer,
        );

        $scope = new Scope(
            evaluator: $evaluator,
        );

        $node = new UnaryOpNode(
            operand: LiteralNode::createBool(true),
            operator: UnarySymbol::NOT,
        );

        $result = $evaluator->unaryOp(
            scope: $scope,
            node: $node,
        );

        self::assertSame($scope, $reducer->lastUnaryOpScope);
        self::assertSame($node, $reducer->lastUnaryOpNode);
        self::assertSame($reducer->unaryOpReturn, $result);
    }

    public function testAssignmentForwardsScopeAndNodeToReducer(): void
    {
        $reducer = new RecordingExpressionReducer();
        $evaluator = new Evaluator(
            expressionReducer: $reducer,
        );

        $scope = new Scope(
            evaluator: $evaluator,
        );

        $node = new AssignmentNode(
            name: new IdentifierNode(
                name: 'x',
            ),
            value: LiteralNode::createInt(1),
            operator: AssignmentSymbol::ADD,
        );

        $result = $evaluator->assignment(
            scope: $scope,
            node: $node,
        );

        self::assertSame($scope, $reducer->lastAssignmentScope);
        self::assertSame($node, $reducer->lastAssignmentNode);
        self::assertSame($reducer->assignmentReturn, $result);
    }

    public function testDereferenceGroupUnwrapsSingleLayerToLiteral(): void
    {
        $literal = LiteralNode::createInt(5);

        self::assertSame(
            $literal,
            $this->evaluator->dereferenceGroup(
                node: new GroupNode(
                    operand: $literal,
                ),
            ),
        );
    }

    public function testDereferenceGroupUnwrapsNestedGroups(): void
    {
        $literal = LiteralNode::createInt(5);

        self::assertSame(
            $literal,
            $this->evaluator->dereferenceGroup(
                node: new GroupNode(
                    operand: new GroupNode(
                        operand: new GroupNode(
                            operand: $literal,
                        ),
                    ),
                ),
            ),
        );
    }

    public function testDereferenceGroupReturnsBinaryOpUnchanged(): void
    {
        $inner = new BinaryOpNode(
            left: LiteralNode::createInt(1),
            right: LiteralNode::createInt(2),
            operator: BinarySymbol::ADD,
        );

        self::assertSame(
            $inner,
            $this->evaluator->dereferenceGroup(
                node: new GroupNode(
                    operand: $inner,
                ),
            ),
        );
    }

    public function testDereferenceIdentifierResolvesToBackingLiteral(): void
    {
        $value = LiteralNode::createInt(5);

        $this->assign(
            name: 'x',
            value: $value,
        );

        self::assertSame(
            $value,
            $this->evaluator->dereferenceIdentifier(
                scope: $this->scope,
                node: new IdentifierNode(
                    name: 'x',
                ),
            ),
        );
    }

    public function testDereferenceIdentifierFollowsChainToTerminalLiteral(): void
    {
        $literal = LiteralNode::createInt(5);

        $this->assign(
            name: 'y',
            value: $literal,
        );
        $this->assign(
            name: 'x',
            value: new IdentifierNode(
                name: 'y',
            ),
        );

        self::assertSame(
            $literal,
            $this->evaluator->dereferenceIdentifier(
                scope: $this->scope,
                node: new IdentifierNode(
                    name: 'x',
                ),
            ),
        );
    }

    public function testDereferenceIdentifierWithUndefinedReturnsNull(): void
    {
        self::assertNull(
            $this->evaluator->dereferenceIdentifier(
                scope: $this->scope,
                node: new IdentifierNode(
                    name: 'missing',
                ),
            ),
        );
    }

    public function testDereferenceIdentifierWithBinaryOpValueReturnsBinaryOp(): void
    {
        $binary = new BinaryOpNode(
            left: new IdentifierNode(
                name: 'a',
            ),
            right: new IdentifierNode(
                name: 'b',
            ),
            operator: BinarySymbol::ADD,
        );

        $this->assign(
            name: 'x',
            value: $binary,
        );

        self::assertSame(
            $binary,
            $this->evaluator->dereferenceIdentifier(
                scope: $this->scope,
                node: new IdentifierNode(
                    name: 'x',
                ),
            ),
        );
    }

    public function testDereferenceWithLiteralReturnsSame(): void
    {
        $literal = LiteralNode::createInt(5);

        self::assertSame(
            $literal,
            $this->evaluator->dereference(
                scope: $this->scope,
                node: $literal,
            ),
        );
    }

    public function testDereferenceWithGroupOfLiteralUnwraps(): void
    {
        $literal = LiteralNode::createInt(5);

        self::assertSame(
            $literal,
            $this->evaluator->dereference(
                scope: $this->scope,
                node: new GroupNode(
                    operand: $literal,
                ),
            ),
        );
    }

    public function testDereferenceWithGroupOfIdentifierResolvesViaScope(): void
    {
        $literal = LiteralNode::createInt(5);

        $this->assign(
            name: 'x',
            value: $literal,
        );

        self::assertSame(
            $literal,
            $this->evaluator->dereference(
                scope: $this->scope,
                node: new GroupNode(
                    operand: new IdentifierNode(
                        name: 'x',
                    ),
                ),
            ),
        );
    }

    public function testDereferenceWithIdentifierResolvesViaScope(): void
    {
        $literal = LiteralNode::createInt(5);

        $this->assign(
            name: 'x',
            value: $literal,
        );

        self::assertSame(
            $literal,
            $this->evaluator->dereference(
                scope: $this->scope,
                node: new IdentifierNode(
                    name: 'x',
                ),
            ),
        );
    }

    public function testDereferenceWithUndefinedIdentifierReturnsNull(): void
    {
        self::assertNull(
            $this->evaluator->dereference(
                scope: $this->scope,
                node: new IdentifierNode(
                    name: 'missing',
                ),
            ),
        );
    }

    public function testDereferenceWithBinaryOpReturnsSame(): void
    {
        $binary = new BinaryOpNode(
            left: LiteralNode::createInt(1),
            right: LiteralNode::createInt(2),
            operator: BinarySymbol::ADD,
        );

        self::assertSame(
            $binary,
            $this->evaluator->dereference(
                scope: $this->scope,
                node: $binary,
            ),
        );
    }

    public function testDereferenceWithGroupOfBinaryOpUnwrapsToBinaryOp(): void
    {
        $binary = new BinaryOpNode(
            left: LiteralNode::createInt(1),
            right: LiteralNode::createInt(2),
            operator: BinarySymbol::ADD,
        );

        self::assertSame(
            $binary,
            $this->evaluator->dereference(
                scope: $this->scope,
                node: new GroupNode(
                    operand: $binary,
                ),
            ),
        );
    }
}
