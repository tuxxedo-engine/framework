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

class BinaryComparisonExpressionTest extends TestCase
{
    use NodeAssertionsTrait;

    private ExpressionParserHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new ExpressionParserHelper();
    }

    public function testParsesImplicitStrictEqual(): void
    {
        $node = $this->helper->parse('a == b');

        $this->assertBinaryOpNode(
            node: $node,
            expectedOperator: BinarySymbol::STRICT_EQUAL_IMPLICIT,
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

    public function testParsesExplicitStrictEqual(): void
    {
        $node = $this->helper->parse('a === b');

        $this->assertBinaryOpNode(
            node: $node,
            expectedOperator: BinarySymbol::STRICT_EQUAL_EXPLICIT,
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

    public function testParsesImplicitStrictNotEqual(): void
    {
        $node = $this->helper->parse('a != b');

        $this->assertBinaryOpNode(
            node: $node,
            expectedOperator: BinarySymbol::STRICT_NOT_EQUAL_IMPLICIT,
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

    public function testParsesExplicitStrictNotEqual(): void
    {
        $node = $this->helper->parse('a !== b');

        $this->assertBinaryOpNode(
            node: $node,
            expectedOperator: BinarySymbol::STRICT_NOT_EQUAL_EXPLICIT,
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

    public function testParsesGreaterThan(): void
    {
        $node = $this->helper->parse('a > b');

        $this->assertBinaryOpNode(
            node: $node,
            expectedOperator: BinarySymbol::GREATER,
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

    public function testParsesLessThan(): void
    {
        $node = $this->helper->parse('a < b');

        $this->assertBinaryOpNode(
            node: $node,
            expectedOperator: BinarySymbol::LESS,
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

    public function testParsesGreaterOrEqual(): void
    {
        $node = $this->helper->parse('a >= b');

        $this->assertBinaryOpNode(
            node: $node,
            expectedOperator: BinarySymbol::GREATER_EQUAL,
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

    public function testParsesLessOrEqual(): void
    {
        $node = $this->helper->parse('a <= b');

        $this->assertBinaryOpNode(
            node: $node,
            expectedOperator: BinarySymbol::LESS_EQUAL,
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
