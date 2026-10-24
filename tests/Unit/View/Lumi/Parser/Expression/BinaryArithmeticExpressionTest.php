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

use PHPUnit\Framework\TestCase;
use Support\View\Lumi\Parser\ExpressionParserHelper;
use Support\View\Lumi\Parser\NodeAssertionsTrait;
use Tuxxedo\View\Lumi\Syntax\Node\BinaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Node\GroupNode;
use Tuxxedo\View\Lumi\Syntax\Node\UnaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Operator\BinarySymbol;
use Tuxxedo\View\Lumi\Syntax\Operator\UnarySymbol;
use Tuxxedo\View\Lumi\Syntax\Type;

class BinaryArithmeticExpressionTest extends TestCase
{
    use NodeAssertionsTrait;

    private ExpressionParserHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new ExpressionParserHelper();
    }

    public function testParsesAddition(): void
    {
        $node = $this->helper->parse('a + b');

        $this->assertBinaryOpNode(
            node: $node,
            expectedOperator: BinarySymbol::ADD,
        );

        self::assertInstanceOf(BinaryOpNode::class, $node);

        $this->assertIdentifierNode(
            node: $node->left,
            expectedName: 'a',
        );

        $this->assertIdentifierNode(
            node: $node->right,
            expectedName: 'b',
        );
    }

    public function testParsesSubtraction(): void
    {
        $node = $this->helper->parse('a - b');

        $this->assertBinaryOpNode(
            node: $node,
            expectedOperator: BinarySymbol::SUBTRACT,
        );

        self::assertInstanceOf(BinaryOpNode::class, $node);

        $this->assertIdentifierNode(
            node: $node->left,
            expectedName: 'a',
        );

        $this->assertIdentifierNode(
            node: $node->right,
            expectedName: 'b',
        );
    }

    public function testParsesMultiplication(): void
    {
        $node = $this->helper->parse('a * b');

        $this->assertBinaryOpNode(
            node: $node,
            expectedOperator: BinarySymbol::MULTIPLY,
        );

        self::assertInstanceOf(BinaryOpNode::class, $node);

        $this->assertIdentifierNode(
            node: $node->left,
            expectedName: 'a',
        );

        $this->assertIdentifierNode(
            node: $node->right,
            expectedName: 'b',
        );
    }

    public function testParsesDivision(): void
    {
        $node = $this->helper->parse('a / b');

        $this->assertBinaryOpNode(
            node: $node,
            expectedOperator: BinarySymbol::DIVIDE,
        );

        self::assertInstanceOf(BinaryOpNode::class, $node);

        $this->assertIdentifierNode(
            node: $node->left,
            expectedName: 'a',
        );

        $this->assertIdentifierNode(
            node: $node->right,
            expectedName: 'b',
        );
    }

    public function testParsesModulus(): void
    {
        $node = $this->helper->parse('a % b');

        $this->assertBinaryOpNode(
            node: $node,
            expectedOperator: BinarySymbol::MODULUS,
        );

        self::assertInstanceOf(BinaryOpNode::class, $node);

        $this->assertIdentifierNode(
            node: $node->left,
            expectedName: 'a',
        );

        $this->assertIdentifierNode(
            node: $node->right,
            expectedName: 'b',
        );
    }

    public function testParsesExponentiation(): void
    {
        $node = $this->helper->parse('a ** b');

        $this->assertBinaryOpNode(
            node: $node,
            expectedOperator: BinarySymbol::EXPONENTIATE,
        );

        self::assertInstanceOf(BinaryOpNode::class, $node);

        $this->assertIdentifierNode(
            node: $node->left,
            expectedName: 'a',
        );

        $this->assertIdentifierNode(
            node: $node->right,
            expectedName: 'b',
        );
    }

    public function testParsesConcatenation(): void
    {
        $node = $this->helper->parse('a ~ b');

        $this->assertBinaryOpNode(
            node: $node,
            expectedOperator: BinarySymbol::CONCAT,
        );

        self::assertInstanceOf(BinaryOpNode::class, $node);

        $this->assertIdentifierNode(
            node: $node->left,
            expectedName: 'a',
        );

        $this->assertIdentifierNode(
            node: $node->right,
            expectedName: 'b',
        );
    }

    public function testNegativeLiteralBaseOfExponentiationBindsLikePhp(): void
    {
        $node = $this->helper->parse('-2 ** 2');

        self::assertInstanceOf(UnaryOpNode::class, $node);
        self::assertSame(UnarySymbol::NEGATE, $node->operator);

        $this->assertBinaryOpNode(
            node: $node->operand,
            expectedOperator: BinarySymbol::EXPONENTIATE,
        );

        self::assertInstanceOf(BinaryOpNode::class, $node->operand);

        $this->assertLiteralNode(
            node: $node->operand->left,
            expectedOperand: '2',
            expectedType: Type::INT,
        );
    }

    public function testNegativeFloatBaseOfExponentiationBindsLikePhp(): void
    {
        $node = $this->helper->parse('-2.5 ** 2');

        self::assertInstanceOf(UnaryOpNode::class, $node);
        self::assertInstanceOf(BinaryOpNode::class, $node->operand);

        $this->assertLiteralNode(
            node: $node->operand->left,
            expectedOperand: '2.5',
            expectedType: Type::FLOAT,
        );
    }

    public function testNegativeLiteralBaseInsideRightAssociativeChain(): void
    {
        $node = $this->helper->parse('-2 ** 3 ** 2');

        self::assertInstanceOf(UnaryOpNode::class, $node);
        self::assertInstanceOf(BinaryOpNode::class, $node->operand);

        $this->assertBinaryOpNode(
            node: $node->operand->right,
            expectedOperator: BinarySymbol::EXPONENTIATE,
        );
    }

    public function testGroupedNegativeBaseKeepsNegativeLiteral(): void
    {
        $node = $this->helper->parse('(-2) ** 2');

        $this->assertBinaryOpNode(
            node: $node,
            expectedOperator: BinarySymbol::EXPONENTIATE,
        );

        self::assertInstanceOf(BinaryOpNode::class, $node);
        self::assertInstanceOf(GroupNode::class, $node->left);
    }

    public function testNegativeExponentStaysLiteral(): void
    {
        $node = $this->helper->parse('2 ** -2');

        $this->assertBinaryOpNode(
            node: $node,
            expectedOperator: BinarySymbol::EXPONENTIATE,
        );

        self::assertInstanceOf(BinaryOpNode::class, $node);

        $this->assertLiteralNode(
            node: $node->right,
            expectedOperand: '-2',
            expectedType: Type::INT,
        );
    }

    public function testNegativeLiteralWithOtherOperatorStaysLiteral(): void
    {
        $node = $this->helper->parse('-2 * 2');

        $this->assertBinaryOpNode(
            node: $node,
            expectedOperator: BinarySymbol::MULTIPLY,
        );

        self::assertInstanceOf(BinaryOpNode::class, $node);

        $this->assertLiteralNode(
            node: $node->left,
            expectedOperand: '-2',
            expectedType: Type::INT,
        );
    }
}
