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
use Tuxxedo\View\Lumi\Syntax\Operator\BinarySymbol;
use Tuxxedo\View\Lumi\Syntax\Type;

class BinaryPrecedenceExpressionTest extends TestCase
{
    use NodeAssertionsTrait;

    private ExpressionParserHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new ExpressionParserHelper();
    }

    public function testMultiplicationBindsTighterThanAddition(): void
    {
        $node = $this->helper->parse('1 + 2 * 3');

        $this->assertBinaryOpNode(
            node: $node,
            expectedOperator: BinarySymbol::ADD,
        );

        self::assertInstanceOf(BinaryOpNode::class, $node);

        $this->assertLiteralNode(
            node: $node->left,
            expectedOperand: '1',
            expectedType: Type::INT,
        );

        $this->assertBinaryOpNode(
            node: $node->right,
            expectedOperator: BinarySymbol::MULTIPLY,
        );

        self::assertInstanceOf(BinaryOpNode::class, $node->right);

        $this->assertLiteralNode(
            node: $node->right->left,
            expectedOperand: '2',
            expectedType: Type::INT,
        );

        $this->assertLiteralNode(
            node: $node->right->right,
            expectedOperand: '3',
            expectedType: Type::INT,
        );
    }

    public function testParenthesesOverridePrecedence(): void
    {
        $node = $this->helper->parse('(1 + 2) * 3');

        $this->assertBinaryOpNode(
            node: $node,
            expectedOperator: BinarySymbol::MULTIPLY,
        );

        self::assertInstanceOf(BinaryOpNode::class, $node);

        $this->assertGroupNode(
            node: $node->left,
        );

        self::assertInstanceOf(GroupNode::class, $node->left);

        $this->assertBinaryOpNode(
            node: $node->left->operand,
            expectedOperator: BinarySymbol::ADD,
        );

        $this->assertLiteralNode(
            node: $node->right,
            expectedOperand: '3',
            expectedType: Type::INT,
        );
    }

    public function testAdditionIsLeftAssociative(): void
    {
        $node = $this->helper->parse('1 + 2 + 3');

        $this->assertBinaryOpNode(
            node: $node,
            expectedOperator: BinarySymbol::ADD,
        );

        self::assertInstanceOf(BinaryOpNode::class, $node);

        $this->assertBinaryOpNode(
            node: $node->left,
            expectedOperator: BinarySymbol::ADD,
        );

        self::assertInstanceOf(BinaryOpNode::class, $node->left);

        $this->assertLiteralNode(
            node: $node->left->left,
            expectedOperand: '1',
            expectedType: Type::INT,
        );

        $this->assertLiteralNode(
            node: $node->left->right,
            expectedOperand: '2',
            expectedType: Type::INT,
        );

        $this->assertLiteralNode(
            node: $node->right,
            expectedOperand: '3',
            expectedType: Type::INT,
        );
    }

    public function testExponentiationIsRightAssociative(): void
    {
        $node = $this->helper->parse('2 ** 3 ** 4');

        $this->assertBinaryOpNode(
            node: $node,
            expectedOperator: BinarySymbol::EXPONENTIATE,
        );

        self::assertInstanceOf(BinaryOpNode::class, $node);

        $this->assertLiteralNode(
            node: $node->left,
            expectedOperand: '2',
            expectedType: Type::INT,
        );

        $this->assertBinaryOpNode(
            node: $node->right,
            expectedOperator: BinarySymbol::EXPONENTIATE,
        );

        self::assertInstanceOf(BinaryOpNode::class, $node->right);

        $this->assertLiteralNode(
            node: $node->right->left,
            expectedOperand: '3',
            expectedType: Type::INT,
        );

        $this->assertLiteralNode(
            node: $node->right->right,
            expectedOperand: '4',
            expectedType: Type::INT,
        );
    }

    public function testNullCoalesceIsRightAssociative(): void
    {
        $node = $this->helper->parse('a ?? b ?? c');

        $this->assertBinaryOpNode(
            node: $node,
            expectedOperator: BinarySymbol::NULL_COALESCE,
        );

        self::assertInstanceOf(BinaryOpNode::class, $node);

        $this->assertIdentifierNode(
            node: $node->left,
            expectedName: 'a',
        );

        $this->assertBinaryOpNode(
            node: $node->right,
            expectedOperator: BinarySymbol::NULL_COALESCE,
        );

        self::assertInstanceOf(BinaryOpNode::class, $node->right);

        $this->assertIdentifierNode(
            node: $node->right->left,
            expectedName: 'b',
        );

        $this->assertIdentifierNode(
            node: $node->right->right,
            expectedName: 'c',
        );
    }

    public function testComparisonBindsTighterThanEquality(): void
    {
        $node = $this->helper->parse('a < b == c');

        $this->assertBinaryOpNode(
            node: $node,
            expectedOperator: BinarySymbol::STRICT_EQUAL_IMPLICIT,
        );

        self::assertInstanceOf(BinaryOpNode::class, $node);

        $this->assertBinaryOpNode(
            node: $node->left,
            expectedOperator: BinarySymbol::LESS,
        );

        $this->assertIdentifierNode(
            node: $node->right,
            expectedName: 'c',
        );
    }
}
