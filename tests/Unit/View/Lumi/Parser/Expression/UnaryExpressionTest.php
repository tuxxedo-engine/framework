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
use Tuxxedo\View\Lumi\Syntax\Node\UnaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Operator\UnarySymbol;

class UnaryExpressionTest extends TestCase
{
    use NodeAssertionsTrait;

    private ExpressionParserHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new ExpressionParserHelper();
    }

    public function testParsesLogicalNot(): void
    {
        $node = $this->helper->parse('!foo');

        $this->assertUnaryOpNode(
            node: $node,
            expectedOperator: UnarySymbol::NOT,
        );

        self::assertInstanceOf(UnaryOpNode::class, $node);

        $this->assertIdentifierNode(
            node: $node->operand,
            expectedName: 'foo',
        );
    }

    public function testParsesNegateOnIdentifier(): void
    {
        $node = $this->helper->parse('-foo');

        $this->assertUnaryOpNode(
            node: $node,
            expectedOperator: UnarySymbol::NEGATE,
        );

        self::assertInstanceOf(UnaryOpNode::class, $node);

        $this->assertIdentifierNode(
            node: $node->operand,
            expectedName: 'foo',
        );
    }

    public function testParsesBitwiseNot(): void
    {
        $node = $this->helper->parse('~foo');

        $this->assertUnaryOpNode(
            node: $node,
            expectedOperator: UnarySymbol::BITWISE_NOT,
        );

        self::assertInstanceOf(UnaryOpNode::class, $node);

        $this->assertIdentifierNode(
            node: $node->operand,
            expectedName: 'foo',
        );
    }

    public function testParsesPreIncrement(): void
    {
        $node = $this->helper->parse('++foo');

        $this->assertUnaryOpNode(
            node: $node,
            expectedOperator: UnarySymbol::INCREMENT_PRE,
        );

        self::assertInstanceOf(UnaryOpNode::class, $node);

        $this->assertIdentifierNode(
            node: $node->operand,
            expectedName: 'foo',
        );
    }

    public function testParsesPostIncrement(): void
    {
        $node = $this->helper->parse('foo++');

        $this->assertUnaryOpNode(
            node: $node,
            expectedOperator: UnarySymbol::INCREMENT_POST,
        );

        self::assertInstanceOf(UnaryOpNode::class, $node);

        $this->assertIdentifierNode(
            node: $node->operand,
            expectedName: 'foo',
        );
    }

    public function testParsesPreDecrement(): void
    {
        $node = $this->helper->parse('--foo');

        $this->assertUnaryOpNode(
            node: $node,
            expectedOperator: UnarySymbol::DECREMENT_PRE,
        );

        self::assertInstanceOf(UnaryOpNode::class, $node);

        $this->assertIdentifierNode(
            node: $node->operand,
            expectedName: 'foo',
        );
    }

    public function testParsesPostDecrement(): void
    {
        $node = $this->helper->parse('foo--');

        $this->assertUnaryOpNode(
            node: $node,
            expectedOperator: UnarySymbol::DECREMENT_POST,
        );

        self::assertInstanceOf(UnaryOpNode::class, $node);

        $this->assertIdentifierNode(
            node: $node->operand,
            expectedName: 'foo',
        );
    }
}
