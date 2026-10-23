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

namespace Unit\View\Lumi\Parser\Expression;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Support\View\Lumi\Parser\ExpressionParserHelper;
use Support\View\Lumi\Parser\NodeAssertionsTrait;
use Tuxxedo\View\Lumi\Lexer\LexerException;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Syntax\Node\ArrayNode;
use Tuxxedo\View\Lumi\Syntax\Node\BinaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Node\CallDispatch;
use Tuxxedo\View\Lumi\Syntax\Node\ExpressionNodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\FilterCallNode;
use Tuxxedo\View\Lumi\Syntax\Node\FilterOrBitwiseOrNode;
use Tuxxedo\View\Lumi\Syntax\Node\FunctionCallNode;
use Tuxxedo\View\Lumi\Syntax\Node\GroupNode;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\PropertyAccessNode;
use Tuxxedo\View\Lumi\Syntax\Node\TernaryNode;
use Tuxxedo\View\Lumi\Syntax\Operator\BinarySymbol;
use Tuxxedo\View\Lumi\Syntax\Type;

class RangeExpressionTest extends TestCase
{
    use NodeAssertionsTrait;

    private ExpressionParserHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new ExpressionParserHelper();
    }

    /**
     * @return array{0: ExpressionNodeInterface, 1: ExpressionNodeInterface}
     */
    private function assertRangeNode(
        NodeInterface $node,
    ): array {
        self::assertInstanceOf(FunctionCallNode::class, $node);
        self::assertSame(CallDispatch::NATIVE, $node->dispatch);
        self::assertCount(2, $node->arguments);

        $this->assertIdentifierNode(
            node: $node->name,
            expectedName: 'range',
        );

        return [
            $node->arguments[0],
            $node->arguments[1],
        ];
    }

    public function testParsesIntegerRange(): void
    {
        [$start, $end] = $this->assertRangeNode($this->helper->parse('1..10'));

        $this->assertLiteralNode(
            node: $start,
            expectedOperand: '1',
            expectedType: Type::INT,
        );

        $this->assertLiteralNode(
            node: $end,
            expectedOperand: '10',
            expectedType: Type::INT,
        );
    }

    public function testParsesStringRange(): void
    {
        [$start, $end] = $this->assertRangeNode($this->helper->parse('\'a\'..\'z\''));

        $this->assertLiteralNode(
            node: $start,
            expectedOperand: 'a',
            expectedType: Type::STRING,
        );

        $this->assertLiteralNode(
            node: $end,
            expectedOperand: 'z',
            expectedType: Type::STRING,
        );
    }

    public function testParsesDescendingRangeWithNegativeEnd(): void
    {
        [$start, $end] = $this->assertRangeNode($this->helper->parse('5..-1'));

        $this->assertLiteralNode(
            node: $start,
            expectedOperand: '5',
            expectedType: Type::INT,
        );

        $this->assertLiteralNode(
            node: $end,
            expectedOperand: '-1',
            expectedType: Type::INT,
        );
    }

    public function testParsesIdentifierRange(): void
    {
        [$start, $end] = $this->assertRangeNode($this->helper->parse('a..b'));

        $this->assertIdentifierNode(
            node: $start,
            expectedName: 'a',
        );

        $this->assertIdentifierNode(
            node: $end,
            expectedName: 'b',
        );
    }

    public function testPropertyAccessBindsTighterThanRange(): void
    {
        [$start, $end] = $this->assertRangeNode($this->helper->parse('pages.first..pages.last'));

        self::assertInstanceOf(PropertyAccessNode::class, $start);
        self::assertSame('first', $start->property);
        self::assertInstanceOf(PropertyAccessNode::class, $end);
        self::assertSame('last', $end->property);
    }

    /**
     * @return \Generator<string, array{0: string, 1: int, 2: BinarySymbol}>
     */
    public static function tighterOperandDataProvider(): \Generator
    {
        yield 'addition on the end' => [
            '1..n + 1',
            1,
            BinarySymbol::ADD,
        ];

        yield 'addition on the start' => [
            '1 + 1..3',
            0,
            BinarySymbol::ADD,
        ];

        yield 'subtraction on the end' => [
            '0..n - 1',
            1,
            BinarySymbol::SUBTRACT,
        ];

        yield 'multiplication on the start' => [
            '2 * 2..10',
            0,
            BinarySymbol::MULTIPLY,
        ];

        yield 'exponentiation on the end' => [
            '1..2 ** 3',
            1,
            BinarySymbol::EXPONENTIATE,
        ];

        yield 'shift on the end' => [
            '1..1 << 3',
            1,
            BinarySymbol::BITWISE_SHIFT_LEFT,
        ];

        yield 'bitwise and on the end' => [
            '1..7 & 3',
            1,
            BinarySymbol::BITWISE_AND,
        ];

        yield 'bitwise xor on the start' => [
            '1 ^ 2..5',
            0,
            BinarySymbol::BITWISE_XOR,
        ];

        yield 'concatenation on the end' => [
            '\'a\'..\'c\' ~ \'\'',
            1,
            BinarySymbol::CONCAT,
        ];
    }

    #[DataProvider('tighterOperandDataProvider')]
    public function testTighterOperatorStaysInsideRangeOperand(
        string $expression,
        int $argumentIndex,
        BinarySymbol $operator,
    ): void {
        $arguments = $this->assertRangeNode($this->helper->parse($expression));

        $this->assertBinaryOpNode(
            node: $arguments[$argumentIndex],
            expectedOperator: $operator,
        );
    }

    /**
     * @return \Generator<string, array{0: string, 1: BinarySymbol, 2: bool}>
     */
    public static function looserOperatorDataProvider(): \Generator
    {
        yield 'equality' => [
            '1..3 == x',
            BinarySymbol::STRICT_EQUAL_IMPLICIT,
            true,
        ];

        yield 'strict inequality' => [
            'x !== 1..3',
            BinarySymbol::STRICT_NOT_EQUAL_EXPLICIT,
            false,
        ];

        yield 'comparison' => [
            '1..3 > x',
            BinarySymbol::GREATER,
            true,
        ];

        yield 'logical and' => [
            '1..3 && x',
            BinarySymbol::AND,
            true,
        ];

        yield 'logical or' => [
            'x || 1..3',
            BinarySymbol::OR,
            false,
        ];

        yield 'null coalesce' => [
            'x ?? 1..3',
            BinarySymbol::NULL_COALESCE,
            false,
        ];
    }

    #[DataProvider('looserOperatorDataProvider')]
    public function testLooserOperatorWrapsRange(
        string $expression,
        BinarySymbol $operator,
        bool $rangeOnLeft,
    ): void {
        $node = $this->helper->parse($expression);

        $this->assertBinaryOpNode(
            node: $node,
            expectedOperator: $operator,
        );

        self::assertInstanceOf(BinaryOpNode::class, $node);

        $this->assertRangeNode(
            $rangeOnLeft
                ? $node->left
                : $node->right,
        );
    }

    public function testRangeIsLeftAssociative(): void
    {
        [$start, $end] = $this->assertRangeNode($this->helper->parse('1..2..3'));

        $this->assertRangeNode($start);

        $this->assertLiteralNode(
            node: $end,
            expectedOperand: '3',
            expectedType: Type::INT,
        );
    }

    public function testFilterCallAppliesToWholeRange(): void
    {
        $node = $this->helper->parse('1..3 | join(\', \')');

        self::assertInstanceOf(FilterCallNode::class, $node);
        self::assertSame('join', $node->name);
        self::assertCount(1, $node->arguments);

        $this->assertRangeNode($node->left);
    }

    public function testBareFilterAppliesToWholeRange(): void
    {
        $node = $this->helper->parse('1..3 | length');

        self::assertInstanceOf(FilterOrBitwiseOrNode::class, $node);

        $this->assertRangeNode($node->left);

        $this->assertIdentifierNode(
            node: $node->right,
            expectedName: 'length',
        );
    }

    public function testRangeOnRightOfPipeIsBitwiseOrNotAFilter(): void
    {
        $node = $this->helper->parse('x | 1..3');

        $this->assertBinaryOpNode(
            node: $node,
            expectedOperator: BinarySymbol::BITWISE_OR,
        );

        self::assertInstanceOf(BinaryOpNode::class, $node);

        $this->assertRangeNode($node->right);
    }

    public function testExplicitRangeCallStaysRuntimeDispatched(): void
    {
        $node = $this->helper->parse('range(1, 3)');

        self::assertInstanceOf(FunctionCallNode::class, $node);
        self::assertSame(CallDispatch::RUNTIME, $node->dispatch);
    }

    public function testExplicitRangeCallOnRightOfPipeIsStillAFilter(): void
    {
        $node = $this->helper->parse('x | range(3)');

        self::assertInstanceOf(FilterCallNode::class, $node);
        self::assertSame('range', $node->name);
    }

    public function testRangeAsFunctionArgument(): void
    {
        $node = $this->helper->parse('count(1..3)');

        self::assertInstanceOf(FunctionCallNode::class, $node);
        self::assertSame(CallDispatch::RUNTIME, $node->dispatch);
        self::assertCount(1, $node->arguments);

        $this->assertRangeNode($node->arguments[0]);
    }

    public function testRangeInGroup(): void
    {
        $node = $this->helper->parse('(1..3)');

        self::assertInstanceOf(GroupNode::class, $node);

        $this->assertRangeNode($node->operand);
    }

    public function testRangesAsArrayItems(): void
    {
        $node = $this->helper->parse('[1..3, k: 4..6]');

        self::assertInstanceOf(ArrayNode::class, $node);
        self::assertCount(2, $node->items);
        self::assertNull($node->items[0]->key);

        $this->assertRangeNode($node->items[0]->value);
        $this->assertRangeNode($node->items[1]->value);

        self::assertInstanceOf(IdentifierNode::class, $node->items[1]->key);
    }

    public function testRangesInTernaryBranches(): void
    {
        $node = $this->helper->parse('c ? 1..3 : 4..6');

        self::assertInstanceOf(TernaryNode::class, $node);
        self::assertNotNull($node->then);

        $this->assertRangeNode($node->then);
        $this->assertRangeNode($node->else);
    }

    public function testRangeAsTernaryCondition(): void
    {
        $node = $this->helper->parse('1..3 ? a : b');

        self::assertInstanceOf(TernaryNode::class, $node);

        $this->assertRangeNode($node->condition);
    }

    public function testRangeWithoutStartThrows(): void
    {
        self::expectException(ParserException::class);

        $this->helper->parse('..5');
    }

    public function testRangeWithoutEndThrows(): void
    {
        self::expectException(LexerException::class);

        $this->helper->parse('1..');
    }

    public function testRangeFollowedByClosingParenthesisThrows(): void
    {
        self::expectException(ParserException::class);

        $this->helper->parse('(1..)');
    }
}
