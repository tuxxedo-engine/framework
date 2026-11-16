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
use Tuxxedo\View\Lumi\Syntax\Node\FilterCallNode;
use Tuxxedo\View\Lumi\Syntax\Node\FilterOrBitwiseOrNode;
use Tuxxedo\View\Lumi\Syntax\Node\GroupNode;
use Tuxxedo\View\Lumi\Syntax\Node\MethodCallNode;
use Tuxxedo\View\Lumi\Syntax\Node\PropertyAccessNode;
use Tuxxedo\View\Lumi\Syntax\Node\UnaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Operator\BinarySymbol;
use Tuxxedo\View\Lumi\Syntax\Operator\UnarySymbol;
use Tuxxedo\View\Lumi\Syntax\Type;

class FilterExpressionTest extends TestCase
{
    use NodeAssertionsTrait;

    private ExpressionParserHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new ExpressionParserHelper();
    }

    public function testParsesFilterPipeBetweenIdentifiers(): void
    {
        $node = $this->helper->parse('a | b');

        $this->assertFilterOrBitwiseOrNode(
            node: $node,
        );

        self::assertInstanceOf(FilterOrBitwiseOrNode::class, $node);

        $this->assertIdentifierNode(
            node: $node->left,
            expectedName: 'a',
        );

        $this->assertIdentifierNode(
            node: $node->right,
            expectedName: 'b',
        );
    }

    public function testParsesFilterPipeOnLiteralLeft(): void
    {
        $node = $this->helper->parse('\'hello\' | upper');

        $this->assertFilterOrBitwiseOrNode(
            node: $node,
        );

        self::assertInstanceOf(FilterOrBitwiseOrNode::class, $node);

        $this->assertLiteralNode(
            node: $node->left,
            expectedOperand: 'hello',
            expectedType: Type::STRING,
        );

        $this->assertIdentifierNode(
            node: $node->right,
            expectedName: 'upper',
        );
    }

    public function testFilterPipeIsLeftAssociative(): void
    {
        $node = $this->helper->parse('a | b | c');

        $this->assertFilterOrBitwiseOrNode(
            node: $node,
        );

        self::assertInstanceOf(FilterOrBitwiseOrNode::class, $node);

        $this->assertFilterOrBitwiseOrNode(
            node: $node->left,
        );

        self::assertInstanceOf(FilterOrBitwiseOrNode::class, $node->left);

        $this->assertIdentifierNode(
            node: $node->left->left,
            expectedName: 'a',
        );

        $this->assertIdentifierNode(
            node: $node->left->right,
            expectedName: 'b',
        );

        $this->assertIdentifierNode(
            node: $node->right,
            expectedName: 'c',
        );
    }

    public function testBitwiseOrWithIntegerLiteralRightIsBinaryOp(): void
    {
        $node = $this->helper->parse('a | 4');

        $this->assertBinaryOpNode(
            node: $node,
            expectedOperator: BinarySymbol::BITWISE_OR,
        );

        self::assertInstanceOf(BinaryOpNode::class, $node);

        $this->assertIdentifierNode(
            node: $node->left,
            expectedName: 'a',
        );

        $this->assertLiteralNode(
            node: $node->right,
            expectedOperand: '4',
            expectedType: Type::INT,
        );
    }

    public function testBitwiseOrWithStringLiteralRightIsBinaryOp(): void
    {
        $node = $this->helper->parse('a | \'flag\'');

        $this->assertBinaryOpNode(
            node: $node,
            expectedOperator: BinarySymbol::BITWISE_OR,
        );

        self::assertInstanceOf(BinaryOpNode::class, $node);

        $this->assertIdentifierNode(
            node: $node->left,
            expectedName: 'a',
        );

        $this->assertLiteralNode(
            node: $node->right,
            expectedOperand: 'flag',
            expectedType: Type::STRING,
        );
    }

    public function testBitwiseOrWithLiteralLeftAndIdentifierRightIsFilter(): void
    {
        $node = $this->helper->parse('1 | b');

        $this->assertFilterOrBitwiseOrNode(
            node: $node,
        );

        self::assertInstanceOf(FilterOrBitwiseOrNode::class, $node);

        $this->assertLiteralNode(
            node: $node->left,
            expectedOperand: '1',
            expectedType: Type::INT,
        );

        $this->assertIdentifierNode(
            node: $node->right,
            expectedName: 'b',
        );
    }

    public function testParsesFilterCallWithArguments(): void
    {
        $node = $this->helper->parse('a | wrap(\'[\', b)');

        self::assertInstanceOf(FilterCallNode::class, $node);
        self::assertSame('wrap', $node->name);
        self::assertCount(2, $node->arguments);

        $this->assertIdentifierNode(
            node: $node->left,
            expectedName: 'a',
        );

        $this->assertLiteralNode(
            node: $node->arguments[0],
            expectedOperand: '[',
            expectedType: Type::STRING,
        );

        $this->assertIdentifierNode(
            node: $node->arguments[1],
            expectedName: 'b',
        );
    }

    public function testFilterCallChainIsLeftAssociative(): void
    {
        $node = $this->helper->parse('a | trim | truncate(20)');

        self::assertInstanceOf(FilterCallNode::class, $node);
        self::assertSame('truncate', $node->name);
        self::assertInstanceOf(FilterOrBitwiseOrNode::class, $node->left);

        $this->assertIdentifierNode(
            node: $node->left->right,
            expectedName: 'trim',
        );
    }

    public function testGroupedCallRightIsFilterOrBitwiseOr(): void
    {
        $node = $this->helper->parse('a | (f(1))');

        self::assertInstanceOf(FilterOrBitwiseOrNode::class, $node);
        self::assertInstanceOf(GroupNode::class, $node->right);
    }

    public function testMethodCallAfterFilterAppliesToFilteredValue(): void
    {
        $node = $this->helper->parse('a | obj.meth(1)');

        self::assertInstanceOf(MethodCallNode::class, $node);
        self::assertSame('meth', $node->name);
        self::assertInstanceOf(FilterOrBitwiseOrNode::class, $node->caller);

        $this->assertIdentifierNode(
            node: $node->caller->left,
            expectedName: 'a',
        );

        $this->assertIdentifierNode(
            node: $node->caller->right,
            expectedName: 'obj',
        );
    }

    public function testConcatAfterFilterAppliesToFilteredValue(): void
    {
        $node = $this->helper->parse('x | upper ~ \'y\'');

        $this->assertBinaryOpNode(
            node: $node,
            expectedOperator: BinarySymbol::CONCAT,
        );

        self::assertInstanceOf(BinaryOpNode::class, $node);
        self::assertInstanceOf(FilterOrBitwiseOrNode::class, $node->left);

        $this->assertIdentifierNode(
            node: $node->left->left,
            expectedName: 'x',
        );

        $this->assertIdentifierNode(
            node: $node->left->right,
            expectedName: 'upper',
        );

        $this->assertLiteralNode(
            node: $node->right,
            expectedOperand: 'y',
            expectedType: Type::STRING,
        );
    }

    public function testAdditionAfterFilterAppliesToFilteredValue(): void
    {
        $node = $this->helper->parse('a | b + 1');

        $this->assertBinaryOpNode(
            node: $node,
            expectedOperator: BinarySymbol::ADD,
        );

        self::assertInstanceOf(BinaryOpNode::class, $node);
        self::assertInstanceOf(FilterOrBitwiseOrNode::class, $node->left);
    }

    public function testMultiplicationAfterFilterAppliesToFilteredValue(): void
    {
        $node = $this->helper->parse('a | b * 2');

        $this->assertBinaryOpNode(
            node: $node,
            expectedOperator: BinarySymbol::MULTIPLY,
        );

        self::assertInstanceOf(BinaryOpNode::class, $node);
        self::assertInstanceOf(FilterOrBitwiseOrNode::class, $node->left);
    }

    public function testPropertyAccessAfterFilterAppliesToFilteredValue(): void
    {
        $node = $this->helper->parse('x | lower.prop');

        self::assertInstanceOf(PropertyAccessNode::class, $node);
        self::assertSame('prop', $node->property);
        self::assertInstanceOf(FilterOrBitwiseOrNode::class, $node->accessor);

        $this->assertIdentifierNode(
            node: $node->accessor->right,
            expectedName: 'lower',
        );
    }

    public function testFilterBindsTighterThanConcatOnItsLeft(): void
    {
        $node = $this->helper->parse('a ~ x | upper');

        $this->assertBinaryOpNode(
            node: $node,
            expectedOperator: BinarySymbol::CONCAT,
        );

        self::assertInstanceOf(BinaryOpNode::class, $node);

        $this->assertIdentifierNode(
            node: $node->left,
            expectedName: 'a',
        );

        self::assertInstanceOf(FilterOrBitwiseOrNode::class, $node->right);

        $this->assertIdentifierNode(
            node: $node->right->left,
            expectedName: 'x',
        );
    }

    public function testFilterBindsTighterThanUnaryNegation(): void
    {
        $node = $this->helper->parse('-x | abs');

        $this->assertUnaryOpNode(
            node: $node,
            expectedOperator: UnarySymbol::NEGATE,
        );

        self::assertInstanceOf(UnaryOpNode::class, $node);
        self::assertInstanceOf(FilterOrBitwiseOrNode::class, $node->operand);

        $this->assertIdentifierNode(
            node: $node->operand->right,
            expectedName: 'abs',
        );
    }

    public function testFilterCallWithArgumentsFollowedByOperator(): void
    {
        $node = $this->helper->parse('a | wrap(\'[\') ~ b');

        $this->assertBinaryOpNode(
            node: $node,
            expectedOperator: BinarySymbol::CONCAT,
        );

        self::assertInstanceOf(BinaryOpNode::class, $node);
        self::assertInstanceOf(FilterCallNode::class, $node->left);
        self::assertSame('wrap', $node->left->name);

        $this->assertIdentifierNode(
            node: $node->right,
            expectedName: 'b',
        );
    }

    public function testGroupedRightKeepsBitwiseOrPrecedence(): void
    {
        $node = $this->helper->parse('a | (b + 1)');

        self::assertInstanceOf(FilterOrBitwiseOrNode::class, $node);
        self::assertInstanceOf(GroupNode::class, $node->right);
    }
}
