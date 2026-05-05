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
use Tuxxedo\View\Lumi\Syntax\Node\MethodCallNode;
use Tuxxedo\View\Lumi\Syntax\Type;

class MethodCallExpressionTest extends TestCase
{
    use NodeAssertionsTrait;

    private ExpressionParserHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new ExpressionParserHelper();
    }

    public function testParsesNoArgumentMethodCall(): void
    {
        $node = $this->helper->parse('user.toUpper()');

        $this->assertMethodCallNode(
            node: $node,
            expectedName: 'toUpper',
            expectedArgumentCount: 0,
        );

        self::assertInstanceOf(MethodCallNode::class, $node);

        $this->assertIdentifierNode(
            node: $node->caller,
            expectedName: 'user',
        );
    }

    public function testParsesMethodCallWithArguments(): void
    {
        $node = $this->helper->parse('user.greet(\'Hi\', 1)');

        $this->assertMethodCallNode(
            node: $node,
            expectedName: 'greet',
            expectedArgumentCount: 2,
        );

        self::assertInstanceOf(MethodCallNode::class, $node);

        $this->assertIdentifierNode(
            node: $node->caller,
            expectedName: 'user',
        );

        $this->assertLiteralNode(
            node: $node->arguments[0],
            expectedOperand: 'Hi',
            expectedType: Type::STRING,
        );

        $this->assertLiteralNode(
            node: $node->arguments[1],
            expectedOperand: '1',
            expectedType: Type::INT,
        );
    }

    public function testParsesNullSafeMethodCall(): void
    {
        $node = $this->helper->parse('user?.toUpper()');

        $this->assertMethodCallNode(
            node: $node,
            expectedName: 'toUpper',
            expectedArgumentCount: 0,
            expectedNullSafe: true,
        );

        self::assertInstanceOf(MethodCallNode::class, $node);

        $this->assertIdentifierNode(
            node: $node->caller,
            expectedName: 'user',
        );
    }

    public function testParsesChainedMethodCallsAreLeftAssociative(): void
    {
        $node = $this->helper->parse('user.profile().email()');

        $this->assertMethodCallNode(
            node: $node,
            expectedName: 'email',
            expectedArgumentCount: 0,
        );

        self::assertInstanceOf(MethodCallNode::class, $node);

        $this->assertMethodCallNode(
            node: $node->caller,
            expectedName: 'profile',
            expectedArgumentCount: 0,
        );

        self::assertInstanceOf(MethodCallNode::class, $node->caller);

        $this->assertIdentifierNode(
            node: $node->caller->caller,
            expectedName: 'user',
        );
    }
}
