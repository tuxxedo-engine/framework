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

class BinaryLogicalExpressionTest extends TestCase
{
    use NodeAssertionsTrait;

    private ExpressionParserHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new ExpressionParserHelper();
    }

    public function testParsesLogicalAnd(): void
    {
        $node = $this->helper->parse('a && b');

        $this->assertBinaryOpNode(
            node: $node,
            expectedOperator: BinarySymbol::AND,
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

    public function testParsesLogicalOr(): void
    {
        $node = $this->helper->parse('a || b');

        $this->assertBinaryOpNode(
            node: $node,
            expectedOperator: BinarySymbol::OR,
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

    public function testParsesLogicalXor(): void
    {
        $node = $this->helper->parse('a ^^ b');

        $this->assertBinaryOpNode(
            node: $node,
            expectedOperator: BinarySymbol::XOR,
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

    public function testParsesNullCoalesce(): void
    {
        $node = $this->helper->parse('a ?? b');

        $this->assertBinaryOpNode(
            node: $node,
            expectedOperator: BinarySymbol::NULL_COALESCE,
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
