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
use Tuxxedo\View\Lumi\Syntax\Operator\BinarySymbol;

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
}
