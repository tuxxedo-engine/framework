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
use Tuxxedo\View\Lumi\Syntax\Node\ArrayAccessNode;
use Tuxxedo\View\Lumi\Syntax\Type;

class ArrayAccessExpressionTest extends TestCase
{
    use NodeAssertionsTrait;

    private ExpressionParserHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new ExpressionParserHelper();
    }

    public function testParsesIntegerKeyedArrayAccess(): void
    {
        $node = $this->helper->parse('users[0]');

        $this->assertArrayAccessNode(
            node: $node,
        );

        self::assertInstanceOf(ArrayAccessNode::class, $node);

        $this->assertIdentifierNode(
            node: $node->array,
            expectedName: 'users',
        );

        self::assertNotNull($node->key);

        $this->assertLiteralNode(
            node: $node->key,
            expectedOperand: '0',
            expectedType: Type::INT,
        );
    }
}
