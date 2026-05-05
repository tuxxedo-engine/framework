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
use Tuxxedo\View\Lumi\Syntax\Node\FunctionCallNode;
use Tuxxedo\View\Lumi\Syntax\Type;

class FunctionCallExpressionTest extends TestCase
{
    use NodeAssertionsTrait;

    private ExpressionParserHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new ExpressionParserHelper();
    }

    public function testParsesNoArgumentFunctionCall(): void
    {
        $node = $this->helper->parse('now()');

        $this->assertFunctionCallNode(
            node: $node,
            expectedArgumentCount: 0,
        );

        self::assertInstanceOf(FunctionCallNode::class, $node);

        $this->assertIdentifierNode(
            node: $node->name,
            expectedName: 'now',
        );
    }

    public function testParsesSingleArgumentFunctionCall(): void
    {
        $node = $this->helper->parse('upper(name)');

        $this->assertFunctionCallNode(
            node: $node,
            expectedArgumentCount: 1,
        );

        self::assertInstanceOf(FunctionCallNode::class, $node);

        $this->assertIdentifierNode(
            node: $node->name,
            expectedName: 'upper',
        );

        $this->assertIdentifierNode(
            node: $node->arguments[0],
            expectedName: 'name',
        );
    }

    public function testParsesMultipleArgumentFunctionCallWithMixedTypes(): void
    {
        $node = $this->helper->parse('format(value, 2, \'usd\')');

        $this->assertFunctionCallNode(
            node: $node,
            expectedArgumentCount: 3,
        );

        self::assertInstanceOf(FunctionCallNode::class, $node);

        $this->assertIdentifierNode(
            node: $node->name,
            expectedName: 'format',
        );

        $this->assertIdentifierNode(
            node: $node->arguments[0],
            expectedName: 'value',
        );

        $this->assertLiteralNode(
            node: $node->arguments[1],
            expectedOperand: '2',
            expectedType: Type::INT,
        );

        $this->assertLiteralNode(
            node: $node->arguments[2],
            expectedOperand: 'usd',
            expectedType: Type::STRING,
        );
    }
}
