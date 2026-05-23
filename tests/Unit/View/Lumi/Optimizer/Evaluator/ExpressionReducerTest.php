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
use Tuxxedo\View\Lumi\Syntax\Node\UnaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Operator\AssignmentSymbol;
use Tuxxedo\View\Lumi\Syntax\Operator\BinarySymbol;
use Tuxxedo\View\Lumi\Syntax\Operator\UnarySymbol;
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

    /**
     * @return \Generator<array{
     *     0: LiteralNode,
     *     1: LiteralNode,
     *     2: bool,
     * }>
     */
    public static function andBoolPairs(): \Generator
    {
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
            LiteralNode::createBool(false),
            LiteralNode::createBool(true),
            false,
        ];

        yield [
            LiteralNode::createBool(false),
            LiteralNode::createBool(false),
            false,
        ];
    }

    #[DataProvider('andBoolPairs')]
    public function testReduceAndCombinesBooleanLiterals(
        LiteralNode $left,
        LiteralNode $right,
        bool $expected,
    ): void {
        $result = $this->reducer->reduceAnd(
            scope: $this->scope,
            left: $left,
            right: $right,
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::BOOL, $result->type);
        self::assertSame(
            $expected
                ? 'true'
                : 'false',
            $result->operand,
        );
    }

    public function testReduceAndCoercesNonBooleanTruthyLiterals(): void
    {
        $result = $this->reducer->reduceAnd(
            scope: $this->scope,
            left: LiteralNode::createInt(1),
            right: LiteralNode::createString('yes'),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::BOOL, $result->type);
        self::assertSame('true', $result->operand);
    }

    public function testReduceAndCoercesNonBooleanFalsyLeftToFalse(): void
    {
        $result = $this->reducer->reduceAnd(
            scope: $this->scope,
            left: LiteralNode::createInt(0),
            right: LiteralNode::createBool(true),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::BOOL, $result->type);
        self::assertSame('false', $result->operand);
    }

    public function testReduceAndResolvesIdentifiersWithComputedValues(): void
    {
        $this->assign(
            name: 'a',
            value: LiteralNode::createBool(true),
        );

        $this->assign(
            name: 'b',
            value: LiteralNode::createBool(true),
        );

        $result = $this->reducer->reduceAnd(
            scope: $this->scope,
            left: new IdentifierNode(
                name: 'a',
            ),
            right: new IdentifierNode(
                name: 'b',
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::BOOL, $result->type);
        self::assertSame('true', $result->operand);
    }

    public function testReduceAndReturnsNullForUnresolvableLeft(): void
    {
        self::assertNull(
            $this->reducer->reduceAnd(
                scope: $this->scope,
                left: new IdentifierNode(
                    name: 'missing',
                ),
                right: LiteralNode::createBool(true),
            ),
        );
    }

    public function testReduceAndReturnsNullForUnresolvableRight(): void
    {
        self::assertNull(
            $this->reducer->reduceAnd(
                scope: $this->scope,
                left: LiteralNode::createBool(true),
                right: new IdentifierNode(
                    name: 'missing',
                ),
            ),
        );
    }

    public function testReduceAndViaReduceBinaryOpDispatch(): void
    {
        $result = $this->reducer->reduceBinaryOp(
            scope: $this->scope,
            node: new BinaryOpNode(
                left: LiteralNode::createBool(true),
                right: LiteralNode::createBool(false),
                operator: BinarySymbol::AND,
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::BOOL, $result->type);
        self::assertSame('false', $result->operand);
    }

    /**
     * @return \Generator<array{
     *     0: LiteralNode,
     *     1: LiteralNode,
     *     2: bool,
     * }>
     */
    public static function xorBoolPairs(): \Generator
    {
        yield [
            LiteralNode::createBool(true),
            LiteralNode::createBool(true),
            false,
        ];

        yield [
            LiteralNode::createBool(true),
            LiteralNode::createBool(false),
            true,
        ];

        yield [
            LiteralNode::createBool(false),
            LiteralNode::createBool(true),
            true,
        ];

        yield [
            LiteralNode::createBool(false),
            LiteralNode::createBool(false),
            false,
        ];
    }

    #[DataProvider('xorBoolPairs')]
    public function testReduceXorCombinesBooleanLiterals(
        LiteralNode $left,
        LiteralNode $right,
        bool $expected,
    ): void {
        $result = $this->reducer->reduceXor(
            scope: $this->scope,
            left: $left,
            right: $right,
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::BOOL, $result->type);
        self::assertSame(
            $expected
                ? 'true'
                : 'false',
            $result->operand,
        );
    }

    public function testReduceXorCoercesNonBooleanTruthyMismatchToTrue(): void
    {
        $result = $this->reducer->reduceXor(
            scope: $this->scope,
            left: LiteralNode::createInt(1),
            right: LiteralNode::createInt(0),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::BOOL, $result->type);
        self::assertSame('true', $result->operand);
    }

    public function testReduceXorCoercesNonBooleanTruthyMatchToFalse(): void
    {
        $result = $this->reducer->reduceXor(
            scope: $this->scope,
            left: LiteralNode::createString('yes'),
            right: LiteralNode::createInt(1),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::BOOL, $result->type);
        self::assertSame('false', $result->operand);
    }

    public function testReduceXorResolvesIdentifiersWithComputedValues(): void
    {
        $this->assign(
            name: 'a',
            value: LiteralNode::createBool(true),
        );

        $this->assign(
            name: 'b',
            value: LiteralNode::createBool(false),
        );

        $result = $this->reducer->reduceXor(
            scope: $this->scope,
            left: new IdentifierNode(
                name: 'a',
            ),
            right: new IdentifierNode(
                name: 'b',
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::BOOL, $result->type);
        self::assertSame('true', $result->operand);
    }

    public function testReduceXorReturnsNullForUnresolvableLeft(): void
    {
        self::assertNull(
            $this->reducer->reduceXor(
                scope: $this->scope,
                left: new IdentifierNode(
                    name: 'missing',
                ),
                right: LiteralNode::createBool(true),
            ),
        );
    }

    public function testReduceXorReturnsNullForUnresolvableRight(): void
    {
        self::assertNull(
            $this->reducer->reduceXor(
                scope: $this->scope,
                left: LiteralNode::createBool(true),
                right: new IdentifierNode(
                    name: 'missing',
                ),
            ),
        );
    }

    public function testReduceXorViaReduceBinaryOpDispatch(): void
    {
        $result = $this->reducer->reduceBinaryOp(
            scope: $this->scope,
            node: new BinaryOpNode(
                left: LiteralNode::createBool(true),
                right: LiteralNode::createBool(false),
                operator: BinarySymbol::XOR,
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::BOOL, $result->type);
        self::assertSame('true', $result->operand);
    }

    #[DataProvider('numericLiteralPairs')]
    public function testReduceGreaterComputesNumericComparison(
        LiteralNode $left,
        LiteralNode $right,
        int|float $leftValue,
        int|float $rightValue,
    ): void {
        $result = $this->reducer->reduceGreater(
            scope: $this->scope,
            left: $left,
            right: $right,
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::BOOL, $result->type);
        self::assertSame(
            $leftValue > $rightValue
                ? 'true'
                : 'false',
            $result->operand,
        );
    }

    #[DataProvider('nonNumericLiteralPairs')]
    public function testReduceGreaterReturnsNullForNonNumericLiterals(
        LiteralNode $left,
        LiteralNode $right,
    ): void {
        self::assertNull(
            $this->reducer->reduceGreater(
                scope: $this->scope,
                left: $left,
                right: $right,
            ),
        );
    }

    public function testReduceGreaterResolvesIdentifiersWithComputedValues(): void
    {
        $this->assign(
            name: 'x',
            value: LiteralNode::createInt(10),
        );

        $this->assign(
            name: 'y',
            value: LiteralNode::createInt(3),
        );

        $result = $this->reducer->reduceGreater(
            scope: $this->scope,
            left: new IdentifierNode(
                name: 'x',
            ),
            right: new IdentifierNode(
                name: 'y',
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::BOOL, $result->type);
        self::assertSame('true', $result->operand);
    }

    public function testReduceGreaterReturnsNullForUnresolvableLeft(): void
    {
        self::assertNull(
            $this->reducer->reduceGreater(
                scope: $this->scope,
                left: new IdentifierNode(
                    name: 'missing',
                ),
                right: LiteralNode::createInt(5),
            ),
        );
    }

    public function testReduceGreaterReturnsNullForUnresolvableRight(): void
    {
        self::assertNull(
            $this->reducer->reduceGreater(
                scope: $this->scope,
                left: LiteralNode::createInt(5),
                right: new IdentifierNode(
                    name: 'missing',
                ),
            ),
        );
    }

    public function testReduceGreaterViaReduceBinaryOpDispatch(): void
    {
        $result = $this->reducer->reduceBinaryOp(
            scope: $this->scope,
            node: new BinaryOpNode(
                left: LiteralNode::createInt(7),
                right: LiteralNode::createInt(3),
                operator: BinarySymbol::GREATER,
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::BOOL, $result->type);
        self::assertSame('true', $result->operand);
    }

    #[DataProvider('numericLiteralPairs')]
    public function testReduceLessComputesNumericComparison(
        LiteralNode $left,
        LiteralNode $right,
        int|float $leftValue,
        int|float $rightValue,
    ): void {
        $result = $this->reducer->reduceLess(
            scope: $this->scope,
            left: $left,
            right: $right,
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::BOOL, $result->type);
        self::assertSame(
            $leftValue < $rightValue
                ? 'true'
                : 'false',
            $result->operand,
        );
    }

    #[DataProvider('nonNumericLiteralPairs')]
    public function testReduceLessReturnsNullForNonNumericLiterals(
        LiteralNode $left,
        LiteralNode $right,
    ): void {
        self::assertNull(
            $this->reducer->reduceLess(
                scope: $this->scope,
                left: $left,
                right: $right,
            ),
        );
    }

    public function testReduceLessResolvesIdentifiersWithComputedValues(): void
    {
        $this->assign(
            name: 'x',
            value: LiteralNode::createInt(2),
        );

        $this->assign(
            name: 'y',
            value: LiteralNode::createInt(9),
        );

        $result = $this->reducer->reduceLess(
            scope: $this->scope,
            left: new IdentifierNode(
                name: 'x',
            ),
            right: new IdentifierNode(
                name: 'y',
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::BOOL, $result->type);
        self::assertSame('true', $result->operand);
    }

    public function testReduceLessReturnsNullForUnresolvableLeft(): void
    {
        self::assertNull(
            $this->reducer->reduceLess(
                scope: $this->scope,
                left: new IdentifierNode(
                    name: 'missing',
                ),
                right: LiteralNode::createInt(5),
            ),
        );
    }

    public function testReduceLessReturnsNullForUnresolvableRight(): void
    {
        self::assertNull(
            $this->reducer->reduceLess(
                scope: $this->scope,
                left: LiteralNode::createInt(5),
                right: new IdentifierNode(
                    name: 'missing',
                ),
            ),
        );
    }

    public function testReduceLessViaReduceBinaryOpDispatch(): void
    {
        $result = $this->reducer->reduceBinaryOp(
            scope: $this->scope,
            node: new BinaryOpNode(
                left: LiteralNode::createInt(2),
                right: LiteralNode::createInt(8),
                operator: BinarySymbol::LESS,
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::BOOL, $result->type);
        self::assertSame('true', $result->operand);
    }

    #[DataProvider('numericLiteralPairs')]
    public function testReduceGreaterEqualComputesNumericComparison(
        LiteralNode $left,
        LiteralNode $right,
        int|float $leftValue,
        int|float $rightValue,
    ): void {
        $result = $this->reducer->reduceGreaterEqual(
            scope: $this->scope,
            left: $left,
            right: $right,
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::BOOL, $result->type);
        self::assertSame(
            $leftValue >= $rightValue
                ? 'true'
                : 'false',
            $result->operand,
        );
    }

    public function testReduceGreaterEqualReturnsTrueForEqualValues(): void
    {
        $result = $this->reducer->reduceGreaterEqual(
            scope: $this->scope,
            left: LiteralNode::createInt(5),
            right: LiteralNode::createInt(5),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::BOOL, $result->type);
        self::assertSame('true', $result->operand);
    }

    #[DataProvider('nonNumericLiteralPairs')]
    public function testReduceGreaterEqualReturnsNullForNonNumericLiterals(
        LiteralNode $left,
        LiteralNode $right,
    ): void {
        self::assertNull(
            $this->reducer->reduceGreaterEqual(
                scope: $this->scope,
                left: $left,
                right: $right,
            ),
        );
    }

    public function testReduceGreaterEqualResolvesIdentifiersWithComputedValues(): void
    {
        $this->assign(
            name: 'x',
            value: LiteralNode::createInt(5),
        );

        $this->assign(
            name: 'y',
            value: LiteralNode::createInt(5),
        );

        $result = $this->reducer->reduceGreaterEqual(
            scope: $this->scope,
            left: new IdentifierNode(
                name: 'x',
            ),
            right: new IdentifierNode(
                name: 'y',
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::BOOL, $result->type);
        self::assertSame('true', $result->operand);
    }

    public function testReduceGreaterEqualReturnsNullForUnresolvableLeft(): void
    {
        self::assertNull(
            $this->reducer->reduceGreaterEqual(
                scope: $this->scope,
                left: new IdentifierNode(
                    name: 'missing',
                ),
                right: LiteralNode::createInt(5),
            ),
        );
    }

    public function testReduceGreaterEqualReturnsNullForUnresolvableRight(): void
    {
        self::assertNull(
            $this->reducer->reduceGreaterEqual(
                scope: $this->scope,
                left: LiteralNode::createInt(5),
                right: new IdentifierNode(
                    name: 'missing',
                ),
            ),
        );
    }

    public function testReduceGreaterEqualViaReduceBinaryOpDispatch(): void
    {
        $result = $this->reducer->reduceBinaryOp(
            scope: $this->scope,
            node: new BinaryOpNode(
                left: LiteralNode::createInt(5),
                right: LiteralNode::createInt(5),
                operator: BinarySymbol::GREATER_EQUAL,
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::BOOL, $result->type);
        self::assertSame('true', $result->operand);
    }

    #[DataProvider('numericLiteralPairs')]
    public function testReduceLessEqualComputesNumericComparison(
        LiteralNode $left,
        LiteralNode $right,
        int|float $leftValue,
        int|float $rightValue,
    ): void {
        $result = $this->reducer->reduceLessEqual(
            scope: $this->scope,
            left: $left,
            right: $right,
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::BOOL, $result->type);
        self::assertSame(
            $leftValue <= $rightValue
                ? 'true'
                : 'false',
            $result->operand,
        );
    }

    public function testReduceLessEqualReturnsTrueForEqualValues(): void
    {
        $result = $this->reducer->reduceLessEqual(
            scope: $this->scope,
            left: LiteralNode::createInt(5),
            right: LiteralNode::createInt(5),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::BOOL, $result->type);
        self::assertSame('true', $result->operand);
    }

    #[DataProvider('nonNumericLiteralPairs')]
    public function testReduceLessEqualReturnsNullForNonNumericLiterals(
        LiteralNode $left,
        LiteralNode $right,
    ): void {
        self::assertNull(
            $this->reducer->reduceLessEqual(
                scope: $this->scope,
                left: $left,
                right: $right,
            ),
        );
    }

    public function testReduceLessEqualResolvesIdentifiersWithComputedValues(): void
    {
        $this->assign(
            name: 'x',
            value: LiteralNode::createInt(3),
        );

        $this->assign(
            name: 'y',
            value: LiteralNode::createInt(3),
        );

        $result = $this->reducer->reduceLessEqual(
            scope: $this->scope,
            left: new IdentifierNode(
                name: 'x',
            ),
            right: new IdentifierNode(
                name: 'y',
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::BOOL, $result->type);
        self::assertSame('true', $result->operand);
    }

    public function testReduceLessEqualReturnsNullForUnresolvableLeft(): void
    {
        self::assertNull(
            $this->reducer->reduceLessEqual(
                scope: $this->scope,
                left: new IdentifierNode(
                    name: 'missing',
                ),
                right: LiteralNode::createInt(5),
            ),
        );
    }

    public function testReduceLessEqualReturnsNullForUnresolvableRight(): void
    {
        self::assertNull(
            $this->reducer->reduceLessEqual(
                scope: $this->scope,
                left: LiteralNode::createInt(5),
                right: new IdentifierNode(
                    name: 'missing',
                ),
            ),
        );
    }

    public function testReduceLessEqualViaReduceBinaryOpDispatch(): void
    {
        $result = $this->reducer->reduceBinaryOp(
            scope: $this->scope,
            node: new BinaryOpNode(
                left: LiteralNode::createInt(5),
                right: LiteralNode::createInt(5),
                operator: BinarySymbol::LESS_EQUAL,
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::BOOL, $result->type);
        self::assertSame('true', $result->operand);
    }

    /**
     * @return \Generator<array{
     *     0: LiteralNode,
     *     1: LiteralNode,
     *     2: int,
     * }>
     */
    public static function exponentiateLiteralPairs(): \Generator
    {
        yield [
            LiteralNode::createInt(2),
            LiteralNode::createInt(3),
            8,
        ];

        yield [
            LiteralNode::createInt(5),
            LiteralNode::createInt(0),
            1,
        ];

        yield [
            LiteralNode::createInt(2),
            LiteralNode::createInt(10),
            1024,
        ];

        yield [
            LiteralNode::createInt(-2),
            LiteralNode::createInt(3),
            -8,
        ];

        yield [
            LiteralNode::createInt(0),
            LiteralNode::createInt(5),
            0,
        ];
    }

    #[DataProvider('exponentiateLiteralPairs')]
    public function testReduceExponentiateComputesIntegerPower(
        LiteralNode $left,
        LiteralNode $right,
        int $expected,
    ): void {
        $result = $this->reducer->reduceExponentiate(
            scope: $this->scope,
            left: $left,
            right: $right,
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::INT, $result->type);
        self::assertSame((string) $expected, $result->operand);
    }

    public function testReduceExponentiateReturnsNullForZeroBaseWithNegativeExponent(): void
    {
        self::assertNull(
            $this->reducer->reduceExponentiate(
                scope: $this->scope,
                left: LiteralNode::createInt(0),
                right: LiteralNode::createInt(-1),
            ),
        );
    }

    #[DataProvider('nonNumericLiteralPairs')]
    public function testReduceExponentiateReturnsNullForNonNumericLiterals(
        LiteralNode $left,
        LiteralNode $right,
    ): void {
        self::assertNull(
            $this->reducer->reduceExponentiate(
                scope: $this->scope,
                left: $left,
                right: $right,
            ),
        );
    }

    public function testReduceExponentiateResolvesIdentifiersWithComputedValues(): void
    {
        $this->assign(
            name: 'base',
            value: LiteralNode::createInt(3),
        );

        $this->assign(
            name: 'exp',
            value: LiteralNode::createInt(4),
        );

        $result = $this->reducer->reduceExponentiate(
            scope: $this->scope,
            left: new IdentifierNode(
                name: 'base',
            ),
            right: new IdentifierNode(
                name: 'exp',
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::INT, $result->type);
        self::assertSame('81', $result->operand);
    }

    public function testReduceExponentiateReturnsNullForUnresolvableLeft(): void
    {
        self::assertNull(
            $this->reducer->reduceExponentiate(
                scope: $this->scope,
                left: new IdentifierNode(
                    name: 'missing',
                ),
                right: LiteralNode::createInt(2),
            ),
        );
    }

    public function testReduceExponentiateReturnsNullForUnresolvableRight(): void
    {
        self::assertNull(
            $this->reducer->reduceExponentiate(
                scope: $this->scope,
                left: LiteralNode::createInt(2),
                right: new IdentifierNode(
                    name: 'missing',
                ),
            ),
        );
    }

    public function testReduceExponentiateTruncatesFloatOperands(): void
    {
        $result = $this->reducer->reduceExponentiate(
            scope: $this->scope,
            left: LiteralNode::createFloat(2.9),
            right: LiteralNode::createFloat(3.7),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::INT, $result->type);
        self::assertSame('8', $result->operand);
    }

    public function testReduceExponentiateViaReduceBinaryOpDispatch(): void
    {
        $result = $this->reducer->reduceBinaryOp(
            scope: $this->scope,
            node: new BinaryOpNode(
                left: LiteralNode::createInt(2),
                right: LiteralNode::createInt(5),
                operator: BinarySymbol::EXPONENTIATE,
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::INT, $result->type);
        self::assertSame('32', $result->operand);
    }

    /**
     * @return \Generator<array{
     *     0: LiteralNode,
     *     1: LiteralNode,
     *     2: int,
     * }>
     */
    public static function bitwiseAndLiteralPairs(): \Generator
    {
        yield [
            LiteralNode::createInt(0b1100),
            LiteralNode::createInt(0b1010),
            0b1000,
        ];

        yield [
            LiteralNode::createInt(0xFF),
            LiteralNode::createInt(0x0F),
            0x0F,
        ];

        yield [
            LiteralNode::createInt(0),
            LiteralNode::createInt(0xFFFF),
            0,
        ];

        yield [
            LiteralNode::createInt(-1),
            LiteralNode::createInt(5),
            5,
        ];
    }

    #[DataProvider('bitwiseAndLiteralPairs')]
    public function testReduceBitwiseAndCombinesIntegers(
        LiteralNode $left,
        LiteralNode $right,
        int $expected,
    ): void {
        $result = $this->reducer->reduceBitwiseAnd(
            scope: $this->scope,
            left: $left,
            right: $right,
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::INT, $result->type);
        self::assertSame((string) $expected, $result->operand);
    }

    #[DataProvider('nonNumericLiteralPairs')]
    public function testReduceBitwiseAndReturnsNullForNonNumericLiterals(
        LiteralNode $left,
        LiteralNode $right,
    ): void {
        self::assertNull(
            $this->reducer->reduceBitwiseAnd(
                scope: $this->scope,
                left: $left,
                right: $right,
            ),
        );
    }

    public function testReduceBitwiseAndResolvesIdentifiersWithComputedValues(): void
    {
        $this->assign(
            name: 'a',
            value: LiteralNode::createInt(0b1110),
        );

        $this->assign(
            name: 'b',
            value: LiteralNode::createInt(0b1011),
        );

        $result = $this->reducer->reduceBitwiseAnd(
            scope: $this->scope,
            left: new IdentifierNode(
                name: 'a',
            ),
            right: new IdentifierNode(
                name: 'b',
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::INT, $result->type);
        self::assertSame((string) 0b1010, $result->operand);
    }

    public function testReduceBitwiseAndReturnsNullForUnresolvableLeft(): void
    {
        self::assertNull(
            $this->reducer->reduceBitwiseAnd(
                scope: $this->scope,
                left: new IdentifierNode(
                    name: 'missing',
                ),
                right: LiteralNode::createInt(5),
            ),
        );
    }

    public function testReduceBitwiseAndReturnsNullForUnresolvableRight(): void
    {
        self::assertNull(
            $this->reducer->reduceBitwiseAnd(
                scope: $this->scope,
                left: LiteralNode::createInt(5),
                right: new IdentifierNode(
                    name: 'missing',
                ),
            ),
        );
    }

    public function testReduceBitwiseAndViaReduceBinaryOpDispatch(): void
    {
        $result = $this->reducer->reduceBinaryOp(
            scope: $this->scope,
            node: new BinaryOpNode(
                left: LiteralNode::createInt(0b1100),
                right: LiteralNode::createInt(0b1010),
                operator: BinarySymbol::BITWISE_AND,
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::INT, $result->type);
        self::assertSame((string) 0b1000, $result->operand);
    }

    /**
     * @return \Generator<array{
     *     0: LiteralNode,
     *     1: LiteralNode,
     *     2: int,
     * }>
     */
    public static function bitwiseOrLiteralPairs(): \Generator
    {
        yield [
            LiteralNode::createInt(0b1100),
            LiteralNode::createInt(0b1010),
            0b1110,
        ];

        yield [
            LiteralNode::createInt(0),
            LiteralNode::createInt(0xFFFF),
            0xFFFF,
        ];

        yield [
            LiteralNode::createInt(0xF0),
            LiteralNode::createInt(0x0F),
            0xFF,
        ];

        yield [
            LiteralNode::createInt(0),
            LiteralNode::createInt(0),
            0,
        ];
    }

    #[DataProvider('bitwiseOrLiteralPairs')]
    public function testReduceBitwiseOrCombinesIntegers(
        LiteralNode $left,
        LiteralNode $right,
        int $expected,
    ): void {
        $result = $this->reducer->reduceBitwiseOr(
            scope: $this->scope,
            left: $left,
            right: $right,
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::INT, $result->type);
        self::assertSame((string) $expected, $result->operand);
    }

    #[DataProvider('nonNumericLiteralPairs')]
    public function testReduceBitwiseOrReturnsNullForNonNumericLiterals(
        LiteralNode $left,
        LiteralNode $right,
    ): void {
        self::assertNull(
            $this->reducer->reduceBitwiseOr(
                scope: $this->scope,
                left: $left,
                right: $right,
            ),
        );
    }

    public function testReduceBitwiseOrResolvesIdentifiersWithComputedValues(): void
    {
        $this->assign(
            name: 'a',
            value: LiteralNode::createInt(0b1100),
        );
        $this->assign(
            name: 'b',
            value: LiteralNode::createInt(0b0011),
        );

        $result = $this->reducer->reduceBitwiseOr(
            scope: $this->scope,
            left: new IdentifierNode(
                name: 'a',
            ),
            right: new IdentifierNode(
                name: 'b',
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::INT, $result->type);
        self::assertSame((string) 0b1111, $result->operand);
    }

    public function testReduceBitwiseOrReturnsNullForUnresolvableLeft(): void
    {
        self::assertNull(
            $this->reducer->reduceBitwiseOr(
                scope: $this->scope,
                left: new IdentifierNode(
                    name: 'missing',
                ),
                right: LiteralNode::createInt(5),
            ),
        );
    }

    public function testReduceBitwiseOrReturnsNullForUnresolvableRight(): void
    {
        self::assertNull(
            $this->reducer->reduceBitwiseOr(
                scope: $this->scope,
                left: LiteralNode::createInt(5),
                right: new IdentifierNode(
                    name: 'missing',
                ),
            ),
        );
    }

    public function testReduceBitwiseOrViaReduceBinaryOpDispatch(): void
    {
        $result = $this->reducer->reduceBinaryOp(
            scope: $this->scope,
            node: new BinaryOpNode(
                left: LiteralNode::createInt(0b1100),
                right: LiteralNode::createInt(0b0011),
                operator: BinarySymbol::BITWISE_OR,
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::INT, $result->type);
        self::assertSame((string) 0b1111, $result->operand);
    }

    /**
     * @return \Generator<array{
     *     0: LiteralNode,
     *     1: LiteralNode,
     *     2: int,
     * }>
     */
    public static function bitwiseXorLiteralPairs(): \Generator
    {
        yield [
            LiteralNode::createInt(0b1100),
            LiteralNode::createInt(0b1010),
            0b0110,
        ];

        yield [
            LiteralNode::createInt(0xFF),
            LiteralNode::createInt(0xFF),
            0,
        ];

        yield [
            LiteralNode::createInt(0),
            LiteralNode::createInt(0xAA),
            0xAA,
        ];

        yield [
            LiteralNode::createInt(0b1111),
            LiteralNode::createInt(0b0101),
            0b1010,
        ];
    }

    #[DataProvider('bitwiseXorLiteralPairs')]
    public function testReduceBitwiseXorCombinesIntegers(
        LiteralNode $left,
        LiteralNode $right,
        int $expected,
    ): void {
        $result = $this->reducer->reduceBitwiseXor(
            scope: $this->scope,
            left: $left,
            right: $right,
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::INT, $result->type);
        self::assertSame((string) $expected, $result->operand);
    }

    #[DataProvider('nonNumericLiteralPairs')]
    public function testReduceBitwiseXorReturnsNullForNonNumericLiterals(
        LiteralNode $left,
        LiteralNode $right,
    ): void {
        self::assertNull(
            $this->reducer->reduceBitwiseXor(
                scope: $this->scope,
                left: $left,
                right: $right,
            ),
        );
    }

    public function testReduceBitwiseXorResolvesIdentifiersWithComputedValues(): void
    {
        $this->assign(
            name: 'a',
            value: LiteralNode::createInt(0b1100),
        );

        $this->assign(
            name: 'b',
            value: LiteralNode::createInt(0b1010),
        );

        $result = $this->reducer->reduceBitwiseXor(
            scope: $this->scope,
            left: new IdentifierNode(
                name: 'a',
            ),
            right: new IdentifierNode(
                name: 'b',
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::INT, $result->type);
        self::assertSame((string) 0b0110, $result->operand);
    }

    public function testReduceBitwiseXorReturnsNullForUnresolvableLeft(): void
    {
        self::assertNull(
            $this->reducer->reduceBitwiseXor(
                scope: $this->scope,
                left: new IdentifierNode(
                    name: 'missing',
                ),
                right: LiteralNode::createInt(5),
            ),
        );
    }

    public function testReduceBitwiseXorReturnsNullForUnresolvableRight(): void
    {
        self::assertNull(
            $this->reducer->reduceBitwiseXor(
                scope: $this->scope,
                left: LiteralNode::createInt(5),
                right: new IdentifierNode(
                    name: 'missing',
                ),
            ),
        );
    }

    public function testReduceBitwiseXorViaReduceBinaryOpDispatch(): void
    {
        $result = $this->reducer->reduceBinaryOp(
            scope: $this->scope,
            node: new BinaryOpNode(
                left: LiteralNode::createInt(0b1100),
                right: LiteralNode::createInt(0b1010),
                operator: BinarySymbol::BITWISE_XOR,
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::INT, $result->type);
        self::assertSame((string) 0b0110, $result->operand);
    }

    /**
     * @return \Generator<array{
     *     0: LiteralNode,
     *     1: LiteralNode,
     *     2: int,
     * }>
     */
    public static function bitwiseShiftLeftLiteralPairs(): \Generator
    {
        yield [
            LiteralNode::createInt(1),
            LiteralNode::createInt(0),
            1,
        ];

        yield [
            LiteralNode::createInt(1),
            LiteralNode::createInt(3),
            8,
        ];

        yield [
            LiteralNode::createInt(0b0001),
            LiteralNode::createInt(4),
            0b10000,
        ];

        yield [
            LiteralNode::createInt(7),
            LiteralNode::createInt(2),
            28,
        ];
    }

    #[DataProvider('bitwiseShiftLeftLiteralPairs')]
    public function testReduceBitwiseShiftLeftShiftsIntegers(
        LiteralNode $left,
        LiteralNode $right,
        int $expected,
    ): void {
        $result = $this->reducer->reduceBitwiseShiftLeft(
            scope: $this->scope,
            left: $left,
            right: $right,
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::INT, $result->type);
        self::assertSame((string) $expected, $result->operand);
    }

    #[DataProvider('nonNumericLiteralPairs')]
    public function testReduceBitwiseShiftLeftReturnsNullForNonNumericLiterals(
        LiteralNode $left,
        LiteralNode $right,
    ): void {
        self::assertNull(
            $this->reducer->reduceBitwiseShiftLeft(
                scope: $this->scope,
                left: $left,
                right: $right,
            ),
        );
    }

    public function testReduceBitwiseShiftLeftResolvesIdentifiersWithComputedValues(): void
    {
        $this->assign(
            name: 'value',
            value: LiteralNode::createInt(1),
        );

        $this->assign(
            name: 'amount',
            value: LiteralNode::createInt(5),
        );

        $result = $this->reducer->reduceBitwiseShiftLeft(
            scope: $this->scope,
            left: new IdentifierNode(
                name: 'value',
            ),
            right: new IdentifierNode(
                name: 'amount',
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::INT, $result->type);
        self::assertSame('32', $result->operand);
    }

    public function testReduceBitwiseShiftLeftReturnsNullForUnresolvableLeft(): void
    {
        self::assertNull(
            $this->reducer->reduceBitwiseShiftLeft(
                scope: $this->scope,
                left: new IdentifierNode(
                    name: 'missing',
                ),
                right: LiteralNode::createInt(2),
            ),
        );
    }

    public function testReduceBitwiseShiftLeftReturnsNullForUnresolvableRight(): void
    {
        self::assertNull(
            $this->reducer->reduceBitwiseShiftLeft(
                scope: $this->scope,
                left: LiteralNode::createInt(2),
                right: new IdentifierNode(
                    name: 'missing',
                ),
            ),
        );
    }

    public function testReduceBitwiseShiftLeftViaReduceBinaryOpDispatch(): void
    {
        $result = $this->reducer->reduceBinaryOp(
            scope: $this->scope,
            node: new BinaryOpNode(
                left: LiteralNode::createInt(1),
                right: LiteralNode::createInt(4),
                operator: BinarySymbol::BITWISE_SHIFT_LEFT,
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::INT, $result->type);
        self::assertSame('16', $result->operand);
    }

    /**
     * @return \Generator<array{
     *     0: LiteralNode,
     *     1: LiteralNode,
     *     2: int,
     * }>
     */
    public static function bitwiseShiftRightLiteralPairs(): \Generator
    {
        yield [
            LiteralNode::createInt(16),
            LiteralNode::createInt(0),
            16,
        ];

        yield [
            LiteralNode::createInt(16),
            LiteralNode::createInt(2),
            4,
        ];

        yield [
            LiteralNode::createInt(0xFF),
            LiteralNode::createInt(4),
            0x0F,
        ];

        yield [
            LiteralNode::createInt(1),
            LiteralNode::createInt(1),
            0,
        ];
    }

    #[DataProvider('bitwiseShiftRightLiteralPairs')]
    public function testReduceBitwiseShiftRightShiftsIntegers(
        LiteralNode $left,
        LiteralNode $right,
        int $expected,
    ): void {
        $result = $this->reducer->reduceBitwiseShiftRight(
            scope: $this->scope,
            left: $left,
            right: $right,
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::INT, $result->type);
        self::assertSame((string) $expected, $result->operand);
    }

    #[DataProvider('nonNumericLiteralPairs')]
    public function testReduceBitwiseShiftRightReturnsNullForNonNumericLiterals(
        LiteralNode $left,
        LiteralNode $right,
    ): void {
        self::assertNull(
            $this->reducer->reduceBitwiseShiftRight(
                scope: $this->scope,
                left: $left,
                right: $right,
            ),
        );
    }

    public function testReduceBitwiseShiftRightResolvesIdentifiersWithComputedValues(): void
    {
        $this->assign(
            name: 'value',
            value: LiteralNode::createInt(64),
        );

        $this->assign(
            name: 'amount',
            value: LiteralNode::createInt(3),
        );

        $result = $this->reducer->reduceBitwiseShiftRight(
            scope: $this->scope,
            left: new IdentifierNode(
                name: 'value',
            ),
            right: new IdentifierNode(
                name: 'amount',
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::INT, $result->type);
        self::assertSame('8', $result->operand);
    }

    public function testReduceBitwiseShiftRightReturnsNullForUnresolvableLeft(): void
    {
        self::assertNull(
            $this->reducer->reduceBitwiseShiftRight(
                scope: $this->scope,
                left: new IdentifierNode(
                    name: 'missing',
                ),
                right: LiteralNode::createInt(2),
            ),
        );
    }

    public function testReduceBitwiseShiftRightReturnsNullForUnresolvableRight(): void
    {
        self::assertNull(
            $this->reducer->reduceBitwiseShiftRight(
                scope: $this->scope,
                left: LiteralNode::createInt(2),
                right: new IdentifierNode(
                    name: 'missing',
                ),
            ),
        );
    }

    public function testReduceBitwiseShiftRightViaReduceBinaryOpDispatch(): void
    {
        $result = $this->reducer->reduceBinaryOp(
            scope: $this->scope,
            node: new BinaryOpNode(
                left: LiteralNode::createInt(32),
                right: LiteralNode::createInt(2),
                operator: BinarySymbol::BITWISE_SHIFT_RIGHT,
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::INT, $result->type);
        self::assertSame('8', $result->operand);
    }

    public function testReduceNullCoalesceReturnsRightWhenLeftIsNullLiteral(): void
    {
        $right = LiteralNode::createString('fallback');

        $result = $this->reducer->reduceNullCoalesce(
            scope: $this->scope,
            left: LiteralNode::createNull(),
            right: $right,
        );

        self::assertSame($right, $result);
    }

    public function testReduceNullCoalesceReturnsNullWhenLeftIsNonNullLiteral(): void
    {
        self::assertNull(
            $this->reducer->reduceNullCoalesce(
                scope: $this->scope,
                left: LiteralNode::createString('value'),
                right: LiteralNode::createString('fallback'),
            ),
        );
    }

    public function testReduceNullCoalesceReturnsNullWhenLeftIsFalseLiteral(): void
    {
        self::assertNull(
            $this->reducer->reduceNullCoalesce(
                scope: $this->scope,
                left: LiteralNode::createBool(false),
                right: LiteralNode::createString('fallback'),
            ),
        );
    }

    public function testReduceNullCoalesceReturnsNullWhenLeftIsZeroLiteral(): void
    {
        self::assertNull(
            $this->reducer->reduceNullCoalesce(
                scope: $this->scope,
                left: LiteralNode::createInt(0),
                right: LiteralNode::createString('fallback'),
            ),
        );
    }

    public function testReduceNullCoalesceReturnsRightWhenLeftIdentifierHoldsNull(): void
    {
        $this->assign(
            name: 'maybe',
            value: LiteralNode::createNull(),
        );

        $right = LiteralNode::createString('fallback');

        $result = $this->reducer->reduceNullCoalesce(
            scope: $this->scope,
            left: new IdentifierNode(
                name: 'maybe',
            ),
            right: $right,
        );

        self::assertSame($right, $result);
    }

    public function testReduceNullCoalesceReturnsNullForUnresolvableLeft(): void
    {
        self::assertNull(
            $this->reducer->reduceNullCoalesce(
                scope: $this->scope,
                left: new IdentifierNode(
                    name: 'missing',
                ),
                right: LiteralNode::createString('fallback'),
            ),
        );
    }

    public function testReduceNullCoalesceViaReduceBinaryOpDispatch(): void
    {
        $right = LiteralNode::createString('fallback');

        $result = $this->reducer->reduceBinaryOp(
            scope: $this->scope,
            node: new BinaryOpNode(
                left: LiteralNode::createNull(),
                right: $right,
                operator: BinarySymbol::NULL_COALESCE,
            ),
        );

        self::assertSame($right, $result);
    }

    public function testReduceBinaryOpReturnsNullForUnsupportedOperator(): void
    {
        self::assertNull(
            $this->reducer->reduceBinaryOp(
                scope: $this->scope,
                node: new BinaryOpNode(
                    left: LiteralNode::createInt(1),
                    right: LiteralNode::createInt(2),
                    operator: BinarySymbol::NULL_SAFE_ACCESS,
                ),
            ),
        );
    }

    /**
     * @return \Generator<array{
     *     0: LiteralNode,
     *     1: LiteralNode,
     *     2: bool,
     * }>
     */
    public static function orBoolPairs(): \Generator
    {
        yield [
            LiteralNode::createBool(true),
            LiteralNode::createBool(true),
            true,
        ];

        yield [
            LiteralNode::createBool(true),
            LiteralNode::createBool(false),
            true,
        ];

        yield [
            LiteralNode::createBool(false),
            LiteralNode::createBool(true),
            true,
        ];

        yield [
            LiteralNode::createBool(false),
            LiteralNode::createBool(false),
            false,
        ];
    }

    #[DataProvider('orBoolPairs')]
    public function testReduceOrCombinesBooleanLiterals(
        LiteralNode $left,
        LiteralNode $right,
        bool $expected,
    ): void {
        $result = $this->reducer->reduceOr(
            scope: $this->scope,
            left: $left,
            right: $right,
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::BOOL, $result->type);
        self::assertSame(
            $expected
                ? 'true'
                : 'false',
            $result->operand,
        );
    }

    public function testReduceOrCoercesNonBooleanTruthyLeftToTrue(): void
    {
        $result = $this->reducer->reduceOr(
            scope: $this->scope,
            left: LiteralNode::createInt(1),
            right: LiteralNode::createBool(false),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::BOOL, $result->type);
        self::assertSame('true', $result->operand);
    }

    public function testReduceOrCoercesNonBooleanFalsyOperandsToFalse(): void
    {
        $result = $this->reducer->reduceOr(
            scope: $this->scope,
            left: LiteralNode::createInt(0),
            right: LiteralNode::createString(''),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::BOOL, $result->type);
        self::assertSame('false', $result->operand);
    }

    public function testReduceOrCoercesNonBooleanTruthyRightToTrue(): void
    {
        $result = $this->reducer->reduceOr(
            scope: $this->scope,
            left: LiteralNode::createBool(false),
            right: LiteralNode::createString('yes'),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::BOOL, $result->type);
        self::assertSame('true', $result->operand);
    }

    public function testReduceOrResolvesIdentifiersWithComputedValues(): void
    {
        $this->assign(
            name: 'a',
            value: LiteralNode::createBool(false),
        );

        $this->assign(
            name: 'b',
            value: LiteralNode::createBool(true),
        );

        $result = $this->reducer->reduceOr(
            scope: $this->scope,
            left: new IdentifierNode(
                name: 'a',
            ),
            right: new IdentifierNode(
                name: 'b',
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::BOOL, $result->type);
        self::assertSame('true', $result->operand);
    }

    public function testReduceOrReturnsNullForUnresolvableLeft(): void
    {
        self::assertNull(
            $this->reducer->reduceOr(
                scope: $this->scope,
                left: new IdentifierNode(
                    name: 'missing',
                ),
                right: LiteralNode::createBool(true),
            ),
        );
    }

    public function testReduceOrReturnsNullForUnresolvableRight(): void
    {
        self::assertNull(
            $this->reducer->reduceOr(
                scope: $this->scope,
                left: LiteralNode::createBool(true),
                right: new IdentifierNode(
                    name: 'missing',
                ),
            ),
        );
    }

    public function testReduceOrViaReduceBinaryOpDispatch(): void
    {
        $result = $this->reducer->reduceBinaryOp(
            scope: $this->scope,
            node: new BinaryOpNode(
                left: LiteralNode::createBool(false),
                right: LiteralNode::createBool(true),
                operator: BinarySymbol::OR,
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::BOOL, $result->type);
        self::assertSame('true', $result->operand);
    }

    /**
     * @return \Generator<array{
     *     0: LiteralNode,
     *     1: bool,
     * }>
     */
    public static function notLiterals(): \Generator
    {
        yield [
            LiteralNode::createBool(true),
            false,
        ];

        yield [
            LiteralNode::createBool(false),
            true,
        ];

        yield [
            LiteralNode::createInt(1),
            false,
        ];

        yield [
            LiteralNode::createInt(0),
            true,
        ];

        yield [
            LiteralNode::createInt(-5),
            false,
        ];

        yield [
            LiteralNode::createFloat(3.14),
            false,
        ];

        yield [
            LiteralNode::createFloat(0.0),
            true,
        ];

        yield [
            LiteralNode::createString('yes'),
            false,
        ];

        yield [
            LiteralNode::createString(''),
            true,
        ];

        yield [
            LiteralNode::createString('0'),
            true,
        ];

        yield [
            LiteralNode::createNull(),
            true,
        ];
    }

    #[DataProvider('notLiterals')]
    public function testReduceNotInvertsTruthinessOfLiterals(
        LiteralNode $expression,
        bool $expected,
    ): void {
        $result = $this->reducer->reduceNot(
            scope: $this->scope,
            expression: $expression,
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::BOOL, $result->type);
        self::assertSame(
            $expected
                ? 'true'
                : 'false',
            $result->operand,
        );
    }

    public function testReduceNotResolvesIdentifierWithComputedValue(): void
    {
        $this->assign(
            name: 'flag',
            value: LiteralNode::createBool(true),
        );

        $result = $this->reducer->reduceNot(
            scope: $this->scope,
            expression: new IdentifierNode(
                name: 'flag',
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::BOOL, $result->type);
        self::assertSame('false', $result->operand);
    }

    public function testReduceNotResolvesIdentifierWithTruthyIntValue(): void
    {
        $this->assign(
            name: 'count',
            value: LiteralNode::createInt(5),
        );

        $result = $this->reducer->reduceNot(
            scope: $this->scope,
            expression: new IdentifierNode(
                name: 'count',
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::BOOL, $result->type);
        self::assertSame('false', $result->operand);
    }

    public function testReduceNotReturnsNullForUnresolvableIdentifier(): void
    {
        self::assertNull(
            $this->reducer->reduceNot(
                scope: $this->scope,
                expression: new IdentifierNode(
                    name: 'missing',
                ),
            ),
        );
    }

    public function testReduceNotViaReduceUnaryOpDispatch(): void
    {
        $result = $this->reducer->reduceUnaryOp(
            scope: $this->scope,
            node: new UnaryOpNode(
                operand: LiteralNode::createBool(true),
                operator: UnarySymbol::NOT,
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::BOOL, $result->type);
        self::assertSame('false', $result->operand);
    }

    /**
     * @return \Generator<array{
     *     0: LiteralNode,
     *     1: Type,
     *     2: string,
     * }>
     */
    public static function negateLiterals(): \Generator
    {
        yield [
            LiteralNode::createInt(5),
            Type::INT,
            '-5',
        ];

        yield [
            LiteralNode::createInt(-7),
            Type::INT,
            '7',
        ];

        yield [
            LiteralNode::createInt(0),
            Type::INT,
            '0',
        ];

        yield [
            LiteralNode::createFloat(3.14),
            Type::FLOAT,
            '-3.14',
        ];

        yield [
            LiteralNode::createFloat(-2.5),
            Type::FLOAT,
            '2.5',
        ];
    }

    #[DataProvider('negateLiterals')]
    public function testReduceNegateInvertsNumericLiterals(
        LiteralNode $expression,
        Type $expectedType,
        string $expectedOperand,
    ): void {
        $result = $this->reducer->reduceNegate(
            scope: $this->scope,
            expression: $expression,
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame($expectedType, $result->type);
        self::assertSame($expectedOperand, $result->operand);
    }

    public function testReduceNegateReturnsNullForStringLiteral(): void
    {
        self::assertNull(
            $this->reducer->reduceNegate(
                scope: $this->scope,
                expression: LiteralNode::createString('5'),
            ),
        );
    }

    public function testReduceNegateReturnsNullForBoolLiteral(): void
    {
        self::assertNull(
            $this->reducer->reduceNegate(
                scope: $this->scope,
                expression: LiteralNode::createBool(true),
            ),
        );
    }

    public function testReduceNegateReturnsNullForNullLiteral(): void
    {
        self::assertNull(
            $this->reducer->reduceNegate(
                scope: $this->scope,
                expression: LiteralNode::createNull(),
            ),
        );
    }

    public function testReduceNegateResolvesIdentifierWithComputedIntValue(): void
    {
        $this->assign(
            name: 'x',
            value: LiteralNode::createInt(42),
        );

        $result = $this->reducer->reduceNegate(
            scope: $this->scope,
            expression: new IdentifierNode(
                name: 'x',
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::INT, $result->type);
        self::assertSame('-42', $result->operand);
    }

    public function testReduceNegateResolvesIdentifierWithComputedFloatValue(): void
    {
        $this->assign(
            name: 'x',
            value: LiteralNode::createFloat(1.5),
        );

        $result = $this->reducer->reduceNegate(
            scope: $this->scope,
            expression: new IdentifierNode(
                name: 'x',
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::FLOAT, $result->type);
        self::assertSame('-1.5', $result->operand);
    }

    public function testReduceNegateReturnsNullForUnresolvableIdentifier(): void
    {
        self::assertNull(
            $this->reducer->reduceNegate(
                scope: $this->scope,
                expression: new IdentifierNode(
                    name: 'missing',
                ),
            ),
        );
    }

    public function testReduceNegateViaReduceUnaryOpDispatch(): void
    {
        $result = $this->reducer->reduceUnaryOp(
            scope: $this->scope,
            node: new UnaryOpNode(
                operand: LiteralNode::createInt(8),
                operator: UnarySymbol::NEGATE,
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::INT, $result->type);
        self::assertSame('-8', $result->operand);
    }

    /**
     * @return \Generator<array{
     *     0: LiteralNode,
     *     1: int,
     * }>
     */
    public static function bitwiseNotLiterals(): \Generator
    {
        yield [
            LiteralNode::createInt(5),
            -6,
        ];

        yield [
            LiteralNode::createInt(0),
            -1,
        ];

        yield [
            LiteralNode::createInt(-1),
            0,
        ];

        yield [
            LiteralNode::createInt(0xFF),
            -256,
        ];

        yield [
            LiteralNode::createString('10'),
            -11,
        ];

        yield [
            LiteralNode::createBool(true),
            -2,
        ];

        yield [
            LiteralNode::createBool(false),
            -1,
        ];

        yield [
            LiteralNode::createNull(),
            -1,
        ];
    }

    #[DataProvider('bitwiseNotLiterals')]
    public function testReduceBitwiseNotInvertsBitsOfLiteralCastToInt(
        LiteralNode $expression,
        int $expected,
    ): void {
        $result = $this->reducer->reduceBitwiseNot(
            scope: $this->scope,
            expression: $expression,
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::INT, $result->type);
        self::assertSame((string) $expected, $result->operand);
    }

    public function testReduceBitwiseNotResolvesIdentifierWithComputedValue(): void
    {
        $this->assign(
            name: 'x',
            value: LiteralNode::createInt(0b1010),
        );

        $result = $this->reducer->reduceBitwiseNot(
            scope: $this->scope,
            expression: new IdentifierNode(
                name: 'x',
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::INT, $result->type);
        self::assertSame((string) ~0b1010, $result->operand);
    }

    public function testReduceBitwiseNotReturnsNullForUnresolvableIdentifier(): void
    {
        self::assertNull(
            $this->reducer->reduceBitwiseNot(
                scope: $this->scope,
                expression: new IdentifierNode(
                    name: 'missing',
                ),
            ),
        );
    }

    public function testReduceBitwiseNotViaReduceUnaryOpDispatch(): void
    {
        $result = $this->reducer->reduceUnaryOp(
            scope: $this->scope,
            node: new UnaryOpNode(
                operand: LiteralNode::createInt(0),
                operator: UnarySymbol::BITWISE_NOT,
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::INT, $result->type);
        self::assertSame('-1', $result->operand);
    }

    public function testReduceIncrementPreReturnsIncrementedIntForIdentifier(): void
    {
        $this->assign(
            name: 'x',
            value: LiteralNode::createInt(5),
        );

        $result = $this->reducer->reduceIncrementPre(
            scope: $this->scope,
            expression: new IdentifierNode(
                name: 'x',
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::INT, $result->type);
        self::assertSame('6', $result->operand);
    }

    public function testReduceIncrementPreReturnsIncrementedFloatForIdentifier(): void
    {
        $this->assign(
            name: 'x',
            value: LiteralNode::createFloat(1.5),
        );

        $result = $this->reducer->reduceIncrementPre(
            scope: $this->scope,
            expression: new IdentifierNode(
                name: 'x',
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::FLOAT, $result->type);
        self::assertSame('2.5', $result->operand);
    }

    public function testReduceIncrementPreMutatesVariableInScope(): void
    {
        $this->assign(
            name: 'x',
            value: LiteralNode::createInt(5),
        );

        $this->reducer->reduceIncrementPre(
            scope: $this->scope,
            expression: new IdentifierNode(
                name: 'x',
            ),
        );

        self::assertSame(
            6,
            $this->scope->get(
                name: new IdentifierNode(
                    name: 'x',
                ),
            )->computedValue,
        );
    }

    public function testReduceIncrementPreReturnsNullForLiteralOperand(): void
    {
        self::assertNull(
            $this->reducer->reduceIncrementPre(
                scope: $this->scope,
                expression: LiteralNode::createInt(5),
            ),
        );
    }

    public function testReduceIncrementPreReturnsNullForUnassignedIdentifier(): void
    {
        self::assertNull(
            $this->reducer->reduceIncrementPre(
                scope: $this->scope,
                expression: new IdentifierNode(
                    name: 'missing',
                ),
            ),
        );
    }

    public function testReduceIncrementPreReturnsNullForNonNumericIdentifier(): void
    {
        $this->assign(
            name: 'x',
            value: LiteralNode::createString('abc'),
        );

        self::assertNull(
            $this->reducer->reduceIncrementPre(
                scope: $this->scope,
                expression: new IdentifierNode(
                    name: 'x',
                ),
            ),
        );
    }

    public function testReduceIncrementPreViaReduceUnaryOpDispatch(): void
    {
        $this->assign(
            name: 'x',
            value: LiteralNode::createInt(10),
        );

        $result = $this->reducer->reduceUnaryOp(
            scope: $this->scope,
            node: new UnaryOpNode(
                operand: new IdentifierNode(
                    name: 'x',
                ),
                operator: UnarySymbol::INCREMENT_PRE,
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::INT, $result->type);
        self::assertSame('11', $result->operand);
    }

    public function testReduceIncrementPostReturnsOriginalIntForIdentifier(): void
    {
        $this->assign(
            name: 'x',
            value: LiteralNode::createInt(5),
        );

        $result = $this->reducer->reduceIncrementPost(
            scope: $this->scope,
            expression: new IdentifierNode(
                name: 'x',
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::INT, $result->type);
        self::assertSame('5', $result->operand);
    }

    public function testReduceIncrementPostReturnsOriginalFloatForIdentifier(): void
    {
        $this->assign(
            name: 'x',
            value: LiteralNode::createFloat(1.5),
        );

        $result = $this->reducer->reduceIncrementPost(
            scope: $this->scope,
            expression: new IdentifierNode(
                name: 'x',
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::FLOAT, $result->type);
        self::assertSame('1.5', $result->operand);
    }

    public function testReduceIncrementPostMutatesVariableInScope(): void
    {
        $this->assign(
            name: 'x',
            value: LiteralNode::createInt(5),
        );

        $this->reducer->reduceIncrementPost(
            scope: $this->scope,
            expression: new IdentifierNode(
                name: 'x',
            ),
        );

        self::assertSame(
            6,
            $this->scope->get(
                name: new IdentifierNode(
                    name: 'x',
                ),
            )->computedValue,
        );
    }

    public function testReduceIncrementPostReturnsNullForLiteralOperand(): void
    {
        self::assertNull(
            $this->reducer->reduceIncrementPost(
                scope: $this->scope,
                expression: LiteralNode::createInt(5),
            ),
        );
    }

    public function testReduceIncrementPostReturnsNullForUnassignedIdentifier(): void
    {
        self::assertNull(
            $this->reducer->reduceIncrementPost(
                scope: $this->scope,
                expression: new IdentifierNode(
                    name: 'missing',
                ),
            ),
        );
    }

    public function testReduceIncrementPostReturnsNullForNonNumericIdentifier(): void
    {
        $this->assign(
            name: 'x',
            value: LiteralNode::createString('abc'),
        );

        self::assertNull(
            $this->reducer->reduceIncrementPost(
                scope: $this->scope,
                expression: new IdentifierNode(
                    name: 'x',
                ),
            ),
        );
    }

    public function testReduceIncrementPostViaReduceUnaryOpDispatch(): void
    {
        $this->assign(
            name: 'x',
            value: LiteralNode::createInt(10),
        );

        $result = $this->reducer->reduceUnaryOp(
            scope: $this->scope,
            node: new UnaryOpNode(
                operand: new IdentifierNode(
                    name: 'x',
                ),
                operator: UnarySymbol::INCREMENT_POST,
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::INT, $result->type);
        self::assertSame('10', $result->operand);
        self::assertSame(
            11,
            $this->scope->get(
                name: new IdentifierNode(
                    name: 'x',
                ),
            )->computedValue,
        );
    }

    public function testReduceDecrementPreReturnsDecrementedIntForIdentifier(): void
    {
        $this->assign(
            name: 'x',
            value: LiteralNode::createInt(5),
        );

        $result = $this->reducer->reduceDecrementPre(
            scope: $this->scope,
            expression: new IdentifierNode(
                name: 'x',
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::INT, $result->type);
        self::assertSame('4', $result->operand);
    }

    public function testReduceDecrementPreReturnsDecrementedFloatForIdentifier(): void
    {
        $this->assign(
            name: 'x',
            value: LiteralNode::createFloat(2.5),
        );

        $result = $this->reducer->reduceDecrementPre(
            scope: $this->scope,
            expression: new IdentifierNode(
                name: 'x',
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::FLOAT, $result->type);
        self::assertSame('1.5', $result->operand);
    }

    public function testReduceDecrementPreMutatesVariableInScope(): void
    {
        $this->assign(
            name: 'x',
            value: LiteralNode::createInt(5),
        );

        $this->reducer->reduceDecrementPre(
            scope: $this->scope,
            expression: new IdentifierNode(
                name: 'x',
            ),
        );

        self::assertSame(
            4,
            $this->scope->get(
                name: new IdentifierNode(
                    name: 'x',
                ),
            )->computedValue,
        );
    }

    public function testReduceDecrementPreReturnsNullForLiteralOperand(): void
    {
        self::assertNull(
            $this->reducer->reduceDecrementPre(
                scope: $this->scope,
                expression: LiteralNode::createInt(5),
            ),
        );
    }

    public function testReduceDecrementPreReturnsNullForUnassignedIdentifier(): void
    {
        self::assertNull(
            $this->reducer->reduceDecrementPre(
                scope: $this->scope,
                expression: new IdentifierNode(
                    name: 'missing',
                ),
            ),
        );
    }

    public function testReduceDecrementPreReturnsNullForNonNumericIdentifier(): void
    {
        $this->assign(
            name: 'x',
            value: LiteralNode::createString('abc'),
        );

        self::assertNull(
            $this->reducer->reduceDecrementPre(
                scope: $this->scope,
                expression: new IdentifierNode(
                    name: 'x',
                ),
            ),
        );
    }

    public function testReduceDecrementPreViaReduceUnaryOpDispatch(): void
    {
        $this->assign(
            name: 'x',
            value: LiteralNode::createInt(10),
        );

        $result = $this->reducer->reduceUnaryOp(
            scope: $this->scope,
            node: new UnaryOpNode(
                operand: new IdentifierNode(
                    name: 'x',
                ),
                operator: UnarySymbol::DECREMENT_PRE,
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::INT, $result->type);
        self::assertSame('9', $result->operand);
    }

    public function testReduceDecrementPostReturnsOriginalIntForIdentifier(): void
    {
        $this->assign(
            name: 'x',
            value: LiteralNode::createInt(5),
        );

        $result = $this->reducer->reduceDecrementPost(
            scope: $this->scope,
            expression: new IdentifierNode(
                name: 'x',
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::INT, $result->type);
        self::assertSame('5', $result->operand);
    }

    public function testReduceDecrementPostReturnsOriginalFloatForIdentifier(): void
    {
        $this->assign(
            name: 'x',
            value: LiteralNode::createFloat(2.5),
        );

        $result = $this->reducer->reduceDecrementPost(
            scope: $this->scope,
            expression: new IdentifierNode(
                name: 'x',
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::FLOAT, $result->type);
        self::assertSame('2.5', $result->operand);
    }

    public function testReduceDecrementPostMutatesVariableInScope(): void
    {
        $this->assign(
            name: 'x',
            value: LiteralNode::createInt(5),
        );

        $this->reducer->reduceDecrementPost(
            scope: $this->scope,
            expression: new IdentifierNode(
                name: 'x',
            ),
        );

        self::assertSame(
            4,
            $this->scope->get(
                name: new IdentifierNode(
                    name: 'x',
                ),
            )->computedValue,
        );
    }

    public function testReduceDecrementPostReturnsNullForLiteralOperand(): void
    {
        self::assertNull(
            $this->reducer->reduceDecrementPost(
                scope: $this->scope,
                expression: LiteralNode::createInt(5),
            ),
        );
    }

    public function testReduceDecrementPostReturnsNullForUnassignedIdentifier(): void
    {
        self::assertNull(
            $this->reducer->reduceDecrementPost(
                scope: $this->scope,
                expression: new IdentifierNode(
                    name: 'missing',
                ),
            ),
        );
    }

    public function testReduceDecrementPostReturnsNullForNonNumericIdentifier(): void
    {
        $this->assign(
            name: 'x',
            value: LiteralNode::createString('abc'),
        );

        self::assertNull(
            $this->reducer->reduceDecrementPost(
                scope: $this->scope,
                expression: new IdentifierNode(
                    name: 'x',
                ),
            ),
        );
    }

    public function testReduceDecrementPostViaReduceUnaryOpDispatch(): void
    {
        $this->assign(
            name: 'x',
            value: LiteralNode::createInt(10),
        );

        $result = $this->reducer->reduceUnaryOp(
            scope: $this->scope,
            node: new UnaryOpNode(
                operand: new IdentifierNode(
                    name: 'x',
                ),
                operator: UnarySymbol::DECREMENT_POST,
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::INT, $result->type);
        self::assertSame('10', $result->operand);
        self::assertSame(
            9,
            $this->scope->get(
                name: new IdentifierNode(
                    name: 'x',
                ),
            )->computedValue,
        );
    }

    public function testReduceAssignmentSubtractCombinesLeftAndRight(): void
    {
        $this->assign(
            name: 'x',
            value: LiteralNode::createInt(10),
        );

        $result = $this->reducer->reduceAssignment(
            scope: $this->scope,
            node: new AssignmentNode(
                name: new IdentifierNode(
                    name: 'x',
                ),
                value: LiteralNode::createInt(3),
                operator: AssignmentSymbol::SUBTRACT,
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::INT, $result->type);
        self::assertSame('7', $result->operand);
    }

    public function testReduceAssignmentMultiplyCombinesLeftAndRight(): void
    {
        $this->assign(
            name: 'x',
            value: LiteralNode::createInt(4),
        );

        $result = $this->reducer->reduceAssignment(
            scope: $this->scope,
            node: new AssignmentNode(
                name: new IdentifierNode(
                    name: 'x',
                ),
                value: LiteralNode::createInt(5),
                operator: AssignmentSymbol::MULTIPLY,
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::INT, $result->type);
        self::assertSame('20', $result->operand);
    }

    public function testReduceAssignmentDivideCombinesLeftAndRight(): void
    {
        $this->assign(
            name: 'x',
            value: LiteralNode::createInt(20),
        );

        $result = $this->reducer->reduceAssignment(
            scope: $this->scope,
            node: new AssignmentNode(
                name: new IdentifierNode(
                    name: 'x',
                ),
                value: LiteralNode::createInt(4),
                operator: AssignmentSymbol::DIVIDE,
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::INT, $result->type);
        self::assertSame('5', $result->operand);
    }

    public function testReduceAssignmentModulusCombinesLeftAndRight(): void
    {
        $this->assign(
            name: 'x',
            value: LiteralNode::createInt(10),
        );

        $result = $this->reducer->reduceAssignment(
            scope: $this->scope,
            node: new AssignmentNode(
                name: new IdentifierNode(
                    name: 'x',
                ),
                value: LiteralNode::createInt(3),
                operator: AssignmentSymbol::MODULUS,
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::INT, $result->type);
        self::assertSame('1', $result->operand);
    }

    public function testReduceAssignmentExponentiateCombinesLeftAndRight(): void
    {
        $this->assign(
            name: 'x',
            value: LiteralNode::createInt(2),
        );

        $result = $this->reducer->reduceAssignment(
            scope: $this->scope,
            node: new AssignmentNode(
                name: new IdentifierNode(
                    name: 'x',
                ),
                value: LiteralNode::createInt(5),
                operator: AssignmentSymbol::EXPONENTIATE,
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::INT, $result->type);
        self::assertSame('32', $result->operand);
    }

    public function testReduceAssignmentBitwiseAndCombinesLeftAndRight(): void
    {
        $this->assign(
            name: 'x',
            value: LiteralNode::createInt(0b1100),
        );

        $result = $this->reducer->reduceAssignment(
            scope: $this->scope,
            node: new AssignmentNode(
                name: new IdentifierNode(
                    name: 'x',
                ),
                value: LiteralNode::createInt(0b1010),
                operator: AssignmentSymbol::BITWISE_AND,
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::INT, $result->type);
        self::assertSame((string) 0b1000, $result->operand);
    }

    public function testReduceAssignmentBitwiseOrCombinesLeftAndRight(): void
    {
        $this->assign(
            name: 'x',
            value: LiteralNode::createInt(0b1100),
        );

        $result = $this->reducer->reduceAssignment(
            scope: $this->scope,
            node: new AssignmentNode(
                name: new IdentifierNode(
                    name: 'x',
                ),
                value: LiteralNode::createInt(0b0011),
                operator: AssignmentSymbol::BITWISE_OR,
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::INT, $result->type);
        self::assertSame((string) 0b1111, $result->operand);
    }

    public function testReduceAssignmentBitwiseXorCombinesLeftAndRight(): void
    {
        $this->assign(
            name: 'x',
            value: LiteralNode::createInt(0b1100),
        );

        $result = $this->reducer->reduceAssignment(
            scope: $this->scope,
            node: new AssignmentNode(
                name: new IdentifierNode(
                    name: 'x',
                ),
                value: LiteralNode::createInt(0b1010),
                operator: AssignmentSymbol::BITWISE_XOR,
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::INT, $result->type);
        self::assertSame((string) 0b0110, $result->operand);
    }

    public function testReduceAssignmentBitwiseShiftLeftCombinesLeftAndRight(): void
    {
        $this->assign(
            name: 'x',
            value: LiteralNode::createInt(1),
        );

        $result = $this->reducer->reduceAssignment(
            scope: $this->scope,
            node: new AssignmentNode(
                name: new IdentifierNode(
                    name: 'x',
                ),
                value: LiteralNode::createInt(4),
                operator: AssignmentSymbol::BITWISE_SHIFT_LEFT,
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::INT, $result->type);
        self::assertSame('16', $result->operand);
    }

    public function testReduceAssignmentBitwiseShiftRightCombinesLeftAndRight(): void
    {
        $this->assign(
            name: 'x',
            value: LiteralNode::createInt(32),
        );

        $result = $this->reducer->reduceAssignment(
            scope: $this->scope,
            node: new AssignmentNode(
                name: new IdentifierNode(
                    name: 'x',
                ),
                value: LiteralNode::createInt(2),
                operator: AssignmentSymbol::BITWISE_SHIFT_RIGHT,
            ),
        );

        self::assertInstanceOf(LiteralNode::class, $result);
        self::assertSame(Type::INT, $result->type);
        self::assertSame('8', $result->operand);
    }

    public function testReduceAssignmentReturnsNullWhenLeftResolvesToNonLiteral(): void
    {
        self::assertNull(
            $this->reducer->reduceAssignment(
                scope: $this->scope,
                node: new AssignmentNode(
                    name: new IdentifierNode(
                        name: 'missing',
                    ),
                    value: LiteralNode::createInt(1),
                    operator: AssignmentSymbol::ADD,
                ),
            ),
        );
    }

    public function testReduceAssignmentReturnsNullForUnsupportedOperator(): void
    {
        $this->assign(
            name: 'x',
            value: LiteralNode::createInt(5),
        );

        self::assertNull(
            $this->reducer->reduceAssignment(
                scope: $this->scope,
                node: new AssignmentNode(
                    name: new IdentifierNode(
                        name: 'x',
                    ),
                    value: LiteralNode::createInt(7),
                    operator: AssignmentSymbol::ASSIGN,
                ),
            ),
        );
    }
}
