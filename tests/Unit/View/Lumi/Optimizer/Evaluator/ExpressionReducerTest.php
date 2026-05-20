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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tuxxedo\View\Lumi\Optimizer\Evaluator\Evaluator;
use Tuxxedo\View\Lumi\Optimizer\Evaluator\ExpressionReducer;
use Tuxxedo\View\Lumi\Optimizer\Scope\Scope;
use Tuxxedo\View\Lumi\Syntax\Node\AssignmentNode;
use Tuxxedo\View\Lumi\Syntax\Node\BinaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Operator\AssignmentSymbol;
use Tuxxedo\View\Lumi\Syntax\Operator\BinarySymbol;
use Tuxxedo\View\Lumi\Syntax\Type;

class ExpressionReducerTest extends TestCase
{
    private Evaluator $evaluator;
    private ExpressionReducer $reducer;
    private Scope $scope;

    protected function setUp(): void
    {
        $this->evaluator = new Evaluator();
        $this->reducer = new ExpressionReducer(
            evaluator: $this->evaluator,
        );

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

    /**
     * @return \Generator<array{
     *     0: LiteralNode,
     *     1: LiteralNode,
     *     2: int|float,
     *     3: int|float,
     * }>
     */
    public static function numericLiteralPairs(): \Generator
    {
        yield [
            LiteralNode::createInt(2),
            LiteralNode::createInt(3),
            2,
            3,
        ];

        yield [
            LiteralNode::createInt(-5),
            LiteralNode::createInt(3),
            -5,
            3,
        ];

        yield [
            LiteralNode::createInt(0),
            LiteralNode::createInt(7),
            0,
            7,
        ];

        yield [
            LiteralNode::createInt(10),
            LiteralNode::createInt(0),
            10,
            0,
        ];

        yield [
            LiteralNode::createFloat(1.5),
            LiteralNode::createFloat(2.5),
            1.5,
            2.5,
        ];

        yield [
            LiteralNode::createFloat(-1.5),
            LiteralNode::createFloat(2.5),
            -1.5,
            2.5,
        ];

        yield [
            LiteralNode::createInt(2),
            LiteralNode::createFloat(0.5),
            2,
            0.5,
        ];

        yield [
            LiteralNode::createFloat(0.5),
            LiteralNode::createInt(2),
            0.5,
            2,
        ];

        yield [
            LiteralNode::createString('42'),
            LiteralNode::createInt(8),
            42,
            8,
        ];

        yield [
            LiteralNode::createString('3.5'),
            LiteralNode::createFloat(0.5),
            3.5,
            0.5,
        ];

        yield [
            LiteralNode::createString('10'),
            LiteralNode::createString('20'),
            10,
            20,
        ];

        yield [
            LiteralNode::createBool(true),
            LiteralNode::createInt(5),
            1,
            5,
        ];

        yield [
            LiteralNode::createBool(false),
            LiteralNode::createInt(5),
            0,
            5,
        ];

        yield [
            LiteralNode::createNull(),
            LiteralNode::createInt(7),
            0,
            7,
        ];

        yield [
            LiteralNode::createInt(1000000),
            LiteralNode::createInt(2000000),
            1000000,
            2000000,
        ];

        yield [
            LiteralNode::createString('-5'),
            LiteralNode::createInt(3),
            -5,
            3,
        ];
    }

    /**
     * @return \Generator<array{
     *     0: LiteralNode,
     *     1: LiteralNode,
     * }>
     */
    public static function nonNumericLiteralPairs(): \Generator
    {
        yield [
            LiteralNode::createString('abc'),
            LiteralNode::createInt(1),
        ];

        yield [
            LiteralNode::createInt(1),
            LiteralNode::createString('abc'),
        ];

        yield [
            LiteralNode::createString('abc'),
            LiteralNode::createString('def'),
        ];

        yield [
            LiteralNode::createString(''),
            LiteralNode::createInt(1),
        ];
    }

    /**
     * @return \Generator<array{
     *     0: LiteralNode,
     *     1: LiteralNode,
     *     2: string,
     * }>
     */
    public static function concatenableLiteralPairs(): \Generator
    {
        yield [
            LiteralNode::createString('hello'),
            LiteralNode::createString('world'),
            'helloworld',
        ];

        yield [
            LiteralNode::createString('foo'),
            LiteralNode::createInt(42),
            'foo42',
        ];

        yield [
            LiteralNode::createInt(2),
            LiteralNode::createInt(3),
            '23',
        ];

        yield [
            LiteralNode::createFloat(1.5),
            LiteralNode::createString('x'),
            '1.5x',
        ];

        yield [
            LiteralNode::createString('value: '),
            LiteralNode::createBool(true),
            'value: 1',
        ];

        yield [
            LiteralNode::createString('value: '),
            LiteralNode::createBool(false),
            'value: ',
        ];

        yield [
            LiteralNode::createString(''),
            LiteralNode::createString(''),
            '',
        ];

        yield [
            LiteralNode::createInt(0),
            LiteralNode::createString('!'),
            '0!',
        ];

        yield [
            LiteralNode::createInt(-42),
            LiteralNode::createString('!'),
            '-42!',
        ];
    }

    /**
     * @return \Generator<array{
     *     0: LiteralNode,
     *     1: LiteralNode,
     *     2: int|float,
     *     3: int|float,
     * }>
     */
    public static function divisibleNumericPairs(): \Generator
    {
        yield [
            LiteralNode::createInt(6),
            LiteralNode::createInt(2),
            6,
            2,
        ];

        yield [
            LiteralNode::createInt(-10),
            LiteralNode::createInt(2),
            -10,
            2,
        ];

        yield [
            LiteralNode::createInt(0),
            LiteralNode::createInt(7),
            0,
            7,
        ];

        yield [
            LiteralNode::createInt(7),
            LiteralNode::createInt(2),
            7,
            2,
        ];

        yield [
            LiteralNode::createFloat(1.5),
            LiteralNode::createFloat(2.5),
            1.5,
            2.5,
        ];

        yield [
            LiteralNode::createFloat(-1.5),
            LiteralNode::createFloat(2.5),
            -1.5,
            2.5,
        ];

        yield [
            LiteralNode::createFloat(0.5),
            LiteralNode::createInt(2),
            0.5,
            2,
        ];

        yield [
            LiteralNode::createInt(5),
            LiteralNode::createFloat(0.5),
            5,
            0.5,
        ];

        yield [
            LiteralNode::createInt(5),
            LiteralNode::createFloat(0.25),
            5,
            0.25,
        ];

        yield [
            LiteralNode::createString('42'),
            LiteralNode::createInt(8),
            42,
            8,
        ];

        yield [
            LiteralNode::createString('10'),
            LiteralNode::createString('20'),
            10,
            20,
        ];

        yield [
            LiteralNode::createBool(true),
            LiteralNode::createInt(5),
            1,
            5,
        ];

        yield [
            LiteralNode::createBool(false),
            LiteralNode::createInt(5),
            0,
            5,
        ];

        yield [
            LiteralNode::createNull(),
            LiteralNode::createInt(7),
            0,
            7,
        ];

        yield [
            LiteralNode::createInt(1000000),
            LiteralNode::createInt(2000000),
            1000000,
            2000000,
        ];

        yield [
            LiteralNode::createInt(10),
            LiteralNode::createString('0.5'),
            10,
            0.5,
        ];
    }

    /**
     * @return \Generator<array{
     *     0: LiteralNode,
     *     1: LiteralNode,
     * }>
     */
    public static function zeroDivisorPairs(): \Generator
    {
        yield [
            LiteralNode::createInt(10),
            LiteralNode::createInt(0),
        ];

        yield [
            LiteralNode::createInt(5),
            LiteralNode::createFloat(0.0),
        ];

        yield [
            LiteralNode::createInt(5),
            LiteralNode::createString('0.0'),
        ];

        yield [
            LiteralNode::createFloat(2.5),
            LiteralNode::createInt(0),
        ];

        yield [
            LiteralNode::createInt(7),
            LiteralNode::createNull(),
        ];

        yield [
            LiteralNode::createInt(7),
            LiteralNode::createBool(false),
        ];
    }

    /**
     * @return \Generator<array{
     *     0: LiteralNode,
     *     1: LiteralNode,
     *     2: bool,
     * }>
     */
    public static function equalLiteralPairs(): \Generator
    {
        yield [
            LiteralNode::createString('hello'),
            LiteralNode::createString('hello'),
            true,
        ];

        yield [
            LiteralNode::createString('hello'),
            LiteralNode::createString('world'),
            false,
        ];

        yield [
            LiteralNode::createInt(5),
            LiteralNode::createInt(5),
            true,
        ];

        yield [
            LiteralNode::createInt(5),
            LiteralNode::createInt(7),
            false,
        ];

        yield [
            LiteralNode::createFloat(3.14),
            LiteralNode::createFloat(3.14),
            true,
        ];

        yield [
            LiteralNode::createFloat(3.14),
            LiteralNode::createFloat(2.71),
            false,
        ];

        yield [
            LiteralNode::createBool(true),
            LiteralNode::createBool(true),
            true,
        ];

        yield [
            LiteralNode::createBool(true),
            LiteralNode::createBool(false),
            false,
        ];

        yield [
            LiteralNode::createNull(),
            LiteralNode::createNull(),
            true,
        ];

        yield [
            LiteralNode::createInt(5),
            LiteralNode::createString('5'),
            false,
        ];

        yield [
            LiteralNode::createInt(5),
            LiteralNode::createFloat(5.0),
            false,
        ];

        yield [
            LiteralNode::createString('true'),
            LiteralNode::createBool(true),
            false,
        ];

        yield [
            LiteralNode::createNull(),
            LiteralNode::createString('null'),
            false,
        ];

        yield [
            new LiteralNode(
                operand: '05',
                type: Type::INT,
            ),
            LiteralNode::createInt(5),
            false,
        ];
    }

    #[DataProvider('concatenableLiteralPairs')]
    public function testReduceConcatJoinsCastValuesIntoString(
        LiteralNode $left,
        LiteralNode $right,
        string $expected,
    ): void {
        $result = $this->reducer->reduceConcat(
            scope: $this->scope,
            left: $left,
            right: $right,
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::STRING, $result->type);
        self::assertSame($expected, $result->operand);
    }

    public function testReduceConcatResolvesIdentifierWithComputedValue(): void
    {
        $this->assign(
            name: 'x',
            value: LiteralNode::createInt(5),
        );

        $result = $this->reducer->reduceConcat(
            scope: $this->scope,
            left: new IdentifierNode(
                name: 'x',
            ),
            right: LiteralNode::createString('!'),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame('5!', $result->operand);
    }

    public function testReduceConcatRecursesIntoNestedBinaryOp(): void
    {
        $inner = new BinaryOpNode(
            left: LiteralNode::createInt(2),
            right: LiteralNode::createInt(3),
            operator: BinarySymbol::ADD,
        );

        $result = $this->reducer->reduceConcat(
            scope: $this->scope,
            left: $inner,
            right: LiteralNode::createString('!'),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame('5!', $result->operand);
    }

    public function testReduceConcatReturnsNullWhenLeftCannotResolve(): void
    {
        self::assertNull(
            $this->reducer->reduceConcat(
                scope: $this->scope,
                left: new IdentifierNode(
                    name: 'missing',
                ),
                right: LiteralNode::createString('!'),
            ),
        );
    }

    public function testReduceConcatReturnsNullWhenRightCannotResolve(): void
    {
        self::assertNull(
            $this->reducer->reduceConcat(
                scope: $this->scope,
                left: LiteralNode::createString('!'),
                right: new IdentifierNode(
                    name: 'missing',
                ),
            ),
        );
    }

    #[DataProvider('numericLiteralPairs')]
    public function testReduceAddSumsNumericLiteralPairs(
        LiteralNode $left,
        LiteralNode $right,
        int|float $leftNumeric,
        int|float $rightNumeric,
    ): void {
        $result = $this->reducer->reduceAdd(
            scope: $this->scope,
            left: $left,
            right: $right,
        );

        self::assertInstanceOf(LiteralNode::class, $result);

        /** @var int|float $actual */
        $actual = $this->evaluator->castNodeToValue($result);

        self::assertSame(
            $leftNumeric + $rightNumeric,
            $actual,
        );
    }

    #[DataProvider('nonNumericLiteralPairs')]
    public function testReduceAddReturnsNullForNonNumericPairs(
        LiteralNode $left,
        LiteralNode $right,
    ): void {
        self::assertNull(
            $this->reducer->reduceAdd(
                scope: $this->scope,
                left: $left,
                right: $right,
            ),
        );
    }

    public function testReduceAddResolvesIdentifierWithComputedValue(): void
    {
        $this->assign(
            name: 'x',
            value: LiteralNode::createInt(5),
        );
        $this->assign(
            name: 'y',
            value: LiteralNode::createInt(3),
        );

        $result = $this->reducer->reduceAdd(
            scope: $this->scope,
            left: new IdentifierNode(
                name: 'x',
            ),
            right: new IdentifierNode(
                name: 'y',
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(
            8,
            $this->evaluator->castNodeToValue($result),
        );
    }

    public function testReduceAddRecursesIntoNestedBinaryOp(): void
    {
        $inner = new BinaryOpNode(
            left: LiteralNode::createInt(2),
            right: LiteralNode::createInt(3),
            operator: BinarySymbol::ADD,
        );

        $result = $this->reducer->reduceAdd(
            scope: $this->scope,
            left: $inner,
            right: LiteralNode::createInt(4),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(
            9,
            $this->evaluator->castNodeToValue($result),
        );
    }

    public function testReduceAddReturnsNullForUndefinedIdentifier(): void
    {
        self::assertNull(
            $this->reducer->reduceAdd(
                scope: $this->scope,
                left: new IdentifierNode(
                    name: 'missing',
                ),
                right: LiteralNode::createInt(1),
            ),
        );
    }

    public function testReduceAddReturnsNullWhenIdentifierResolvesToNonNumeric(): void
    {
        $this->assign(
            name: 'x',
            value: LiteralNode::createString('abc'),
        );

        self::assertNull(
            $this->reducer->reduceAdd(
                scope: $this->scope,
                left: new IdentifierNode(
                    name: 'x',
                ),
                right: LiteralNode::createInt(1),
            ),
        );
    }

    #[DataProvider('numericLiteralPairs')]
    public function testReduceSubtractDifferencesNumericLiteralPairs(
        LiteralNode $left,
        LiteralNode $right,
        int|float $leftNumeric,
        int|float $rightNumeric,
    ): void {
        $result = $this->reducer->reduceSubtract(
            scope: $this->scope,
            left: $left,
            right: $right,
        );

        self::assertInstanceOf(LiteralNode::class, $result);

        /** @var int|float $actual */
        $actual = $this->evaluator->castNodeToValue($result);

        self::assertSame(
            $leftNumeric - $rightNumeric,
            $actual,
        );
    }

    #[DataProvider('nonNumericLiteralPairs')]
    public function testReduceSubtractReturnsNullForNonNumericPairs(
        LiteralNode $left,
        LiteralNode $right,
    ): void {
        self::assertNull(
            $this->reducer->reduceSubtract(
                scope: $this->scope,
                left: $left,
                right: $right,
            ),
        );
    }

    public function testReduceSubtractResolvesIdentifierWithComputedValue(): void
    {
        $this->assign(
            name: 'x',
            value: LiteralNode::createInt(10),
        );

        $this->assign(
            name: 'y',
            value: LiteralNode::createInt(3),
        );

        $result = $this->reducer->reduceSubtract(
            scope: $this->scope,
            left: new IdentifierNode(
                name: 'x',
            ),
            right: new IdentifierNode(
                name: 'y',
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(
            7,
            $this->evaluator->castNodeToValue($result),
        );
    }

    public function testReduceSubtractReturnsNullForUndefinedIdentifier(): void
    {
        self::assertNull(
            $this->reducer->reduceSubtract(
                scope: $this->scope,
                left: LiteralNode::createInt(5),
                right: new IdentifierNode(
                    name: 'missing',
                ),
            ),
        );
    }

    #[DataProvider('numericLiteralPairs')]
    public function testReduceMultiplyProductsNumericLiteralPairs(
        LiteralNode $left,
        LiteralNode $right,
        int|float $leftNumeric,
        int|float $rightNumeric,
    ): void {
        $result = $this->reducer->reduceMultiply(
            scope: $this->scope,
            left: $left,
            right: $right,
        );

        self::assertInstanceOf(LiteralNode::class, $result);

        /** @var int|float $actual */
        $actual = $this->evaluator->castNodeToValue($result);

        self::assertSame(
            $leftNumeric * $rightNumeric,
            $actual,
        );
    }

    #[DataProvider('nonNumericLiteralPairs')]
    public function testReduceMultiplyReturnsNullForNonNumericPairs(
        LiteralNode $left,
        LiteralNode $right,
    ): void {
        self::assertNull(
            $this->reducer->reduceMultiply(
                scope: $this->scope,
                left: $left,
                right: $right,
            ),
        );
    }

    public function testReduceMultiplyResolvesIdentifierWithComputedValue(): void
    {
        $this->assign(
            name: 'x',
            value: LiteralNode::createInt(4),
        );

        $this->assign(
            name: 'y',
            value: LiteralNode::createInt(6),
        );

        $result = $this->reducer->reduceMultiply(
            scope: $this->scope,
            left: new IdentifierNode(
                name: 'x',
            ),
            right: new IdentifierNode(
                name: 'y',
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(
            24,
            $this->evaluator->castNodeToValue($result),
        );
    }

    #[DataProvider('divisibleNumericPairs')]
    public function testReduceDivideQuotientsDivisibleNumericPairs(
        LiteralNode $left,
        LiteralNode $right,
        int|float $leftNumeric,
        int|float $rightNumeric,
    ): void {
        $result = $this->reducer->reduceDivide(
            scope: $this->scope,
            left: $left,
            right: $right,
        );

        self::assertInstanceOf(LiteralNode::class, $result);

        /** @var int|float $actual */
        $actual = $this->evaluator->castNodeToValue($result);

        self::assertSame(
            $leftNumeric / $rightNumeric,
            $actual,
        );
    }

    #[DataProvider('nonNumericLiteralPairs')]
    public function testReduceDivideReturnsNullForNonNumericPairs(
        LiteralNode $left,
        LiteralNode $right,
    ): void {
        self::assertNull(
            $this->reducer->reduceDivide(
                scope: $this->scope,
                left: $left,
                right: $right,
            ),
        );
    }

    #[DataProvider('zeroDivisorPairs')]
    public function testReduceDivideReturnsNullForZeroDivisor(
        LiteralNode $left,
        LiteralNode $right,
    ): void {
        self::assertNull(
            $this->reducer->reduceDivide(
                scope: $this->scope,
                left: $left,
                right: $right,
            ),
        );
    }

    public function testReduceDivideReturnsNullForUndefinedIdentifier(): void
    {
        self::assertNull(
            $this->reducer->reduceDivide(
                scope: $this->scope,
                left: LiteralNode::createInt(10),
                right: new IdentifierNode(
                    name: 'missing',
                ),
            ),
        );
    }

    #[DataProvider('divisibleNumericPairs')]
    public function testReduceModulusProducesIntCoercedRemainderForDivisibleNumericPairs(
        LiteralNode $left,
        LiteralNode $right,
        int|float $leftNumeric,
        int|float $rightNumeric,
    ): void {
        $result = $this->reducer->reduceModulus(
            scope: $this->scope,
            left: $left,
            right: $right,
        );

        self::assertInstanceOf(LiteralNode::class, $result);

        /** @var int|float $actual */
        $actual = $this->evaluator->castNodeToValue($result);

        self::assertSame(
            \is_float($actual)
                ? \fmod(\floatval($leftNumeric), \floatval($rightNumeric))
                : \intval($leftNumeric) % \intval($rightNumeric),
            $actual,
        );
    }

    #[DataProvider('nonNumericLiteralPairs')]
    public function testReduceModulusReturnsNullForNonNumericPairs(
        LiteralNode $left,
        LiteralNode $right,
    ): void {
        self::assertNull(
            $this->reducer->reduceModulus(
                scope: $this->scope,
                left: $left,
                right: $right,
            ),
        );
    }

    #[DataProvider('zeroDivisorPairs')]
    public function testReduceModulusReturnsNullForZeroDivisor(
        LiteralNode $left,
        LiteralNode $right,
    ): void {
        self::assertNull(
            $this->reducer->reduceModulus(
                scope: $this->scope,
                left: $left,
                right: $right,
            ),
        );
    }

    #[DataProvider('equalLiteralPairs')]
    public function testReduceEqualComparesOperandsStrictlyAfterTypeMatch(
        LiteralNode $left,
        LiteralNode $right,
        bool $expectedEqual,
    ): void {
        $result = $this->reducer->reduceEqual(
            scope: $this->scope,
            left: $left,
            right: $right,
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::BOOL, $result->type);
        self::assertSame(
            $expectedEqual
                ? 'true'
                : 'false',
            $result->operand,
        );
    }

    public function testReduceEqualResolvesIdentifierWithComputedValue(): void
    {
        $this->assign(
            name: 'x',
            value: LiteralNode::createInt(5),
        );

        $result = $this->reducer->reduceEqual(
            scope: $this->scope,
            left: new IdentifierNode(
                name: 'x',
            ),
            right: LiteralNode::createInt(5),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::BOOL, $result->type);
        self::assertSame('true', $result->operand);
    }

    public function testReduceEqualReturnsNullForUnresolvableLeft(): void
    {
        self::assertNull(
            $this->reducer->reduceEqual(
                scope: $this->scope,
                left: new IdentifierNode(
                    name: 'missing',
                ),
                right: LiteralNode::createInt(5),
            ),
        );
    }

    public function testReduceEqualReturnsNullForUnresolvableRight(): void
    {
        self::assertNull(
            $this->reducer->reduceEqual(
                scope: $this->scope,
                left: LiteralNode::createInt(5),
                right: new IdentifierNode(
                    name: 'missing',
                ),
            ),
        );
    }

    #[DataProvider('equalLiteralPairs')]
    public function testReduceNotEqualInvertsEqualResult(
        LiteralNode $left,
        LiteralNode $right,
        bool $expectedEqual,
    ): void {
        $result = $this->reducer->reduceNotEqual(
            scope: $this->scope,
            left: $left,
            right: $right,
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::BOOL, $result->type);
        self::assertSame(
            $expectedEqual
                ? 'false'
                : 'true',
            $result->operand,
        );
    }

    public function testReduceNotEqualReturnsNullForUnresolvableLeft(): void
    {
        self::assertNull(
            $this->reducer->reduceNotEqual(
                scope: $this->scope,
                left: new IdentifierNode(
                    name: 'missing',
                ),
                right: LiteralNode::createInt(5),
            ),
        );
    }

    public function testReduceNotEqualReturnsNullForUnresolvableRight(): void
    {
        self::assertNull(
            $this->reducer->reduceNotEqual(
                scope: $this->scope,
                left: LiteralNode::createInt(5),
                right: new IdentifierNode(
                    name: 'missing',
                ),
            ),
        );
    }
}
