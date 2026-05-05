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
use Tuxxedo\View\Lumi\Syntax\Node\GroupNode;
use Tuxxedo\View\Lumi\Syntax\Operator\BinarySymbol;
use Tuxxedo\View\Lumi\Syntax\Type;

class GroupExpressionTest extends TestCase
{
    use NodeAssertionsTrait;

    private ExpressionParserHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new ExpressionParserHelper();
    }

    public function testParsesGroupedIdentifier(): void
    {
        $node = $this->helper->parse('(foo)');

        $this->assertGroupNode(
            node: $node,
        );

        self::assertInstanceOf(GroupNode::class, $node);

        $this->assertIdentifierNode(
            node: $node->operand,
            expectedName: 'foo',
        );
    }

    public function testParsesGroupedLiteral(): void
    {
        $node = $this->helper->parse('(42)');

        $this->assertGroupNode(
            node: $node,
        );

        self::assertInstanceOf(GroupNode::class, $node);

        $this->assertLiteralNode(
            node: $node->operand,
            expectedOperand: '42',
            expectedType: Type::INT,
        );
    }

    public function testParsesNestedGroups(): void
    {
        $node = $this->helper->parse('((foo))');

        $this->assertGroupNode(
            node: $node,
        );

        self::assertInstanceOf(GroupNode::class, $node);

        $this->assertGroupNode(
            node: $node->operand,
        );

        self::assertInstanceOf(GroupNode::class, $node->operand);

        $this->assertIdentifierNode(
            node: $node->operand->operand,
            expectedName: 'foo',
        );
    }

    public function testParsesGroupedBinaryExpression(): void
    {
        $node = $this->helper->parse('(a + b)');

        $this->assertGroupNode(node: $node);

        self::assertInstanceOf(GroupNode::class, $node);

        $this->assertBinaryOpNode(
            node: $node->operand,
            expectedOperator: BinarySymbol::ADD,
        );
    }
}
