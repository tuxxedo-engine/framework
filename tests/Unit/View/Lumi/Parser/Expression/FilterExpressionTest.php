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
use Tuxxedo\View\Lumi\Syntax\Node\FilterOrBitwiseOrNode;
use Tuxxedo\View\Lumi\Syntax\Operator\BinarySymbol;
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
}
