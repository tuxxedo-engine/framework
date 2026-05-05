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
use Tuxxedo\View\Lumi\Syntax\Node\PropertyAccessNode;

class PropertyAccessExpressionTest extends TestCase
{
    use NodeAssertionsTrait;

    private ExpressionParserHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new ExpressionParserHelper();
    }

    public function testParsesPropertyAccess(): void
    {
        $node = $this->helper->parse('user.name');

        $this->assertPropertyAccessNode(
            node: $node,
            expectedProperty: 'name',
        );

        self::assertInstanceOf(PropertyAccessNode::class, $node);

        $this->assertIdentifierNode(
            node: $node->accessor,
            expectedName: 'user',
        );
    }

    public function testParsesNullSafePropertyAccess(): void
    {
        $node = $this->helper->parse('user?.name');

        $this->assertPropertyAccessNode(
            node: $node,
            expectedProperty: 'name',
            expectedNullSafe: true,
        );

        self::assertInstanceOf(PropertyAccessNode::class, $node);

        $this->assertIdentifierNode(
            node: $node->accessor,
            expectedName: 'user',
        );
    }

    public function testParsesChainedPropertyAccessIsLeftAssociative(): void
    {
        $node = $this->helper->parse('user.profile.email');

        $this->assertPropertyAccessNode(
            node: $node,
            expectedProperty: 'email',
        );

        self::assertInstanceOf(PropertyAccessNode::class, $node);

        $this->assertPropertyAccessNode(
            node: $node->accessor,
            expectedProperty: 'profile',
        );

        self::assertInstanceOf(PropertyAccessNode::class, $node->accessor);

        $this->assertIdentifierNode(
            node: $node->accessor->accessor,
            expectedName: 'user',
        );
    }

    public function testParsesMixedNullSafeAndStandardChain(): void
    {
        $node = $this->helper->parse('user?.profile.email');

        $this->assertPropertyAccessNode(
            node: $node,
            expectedProperty: 'email',
            expectedNullSafe: false,
        );

        self::assertInstanceOf(PropertyAccessNode::class, $node);

        $this->assertPropertyAccessNode(
            node: $node->accessor,
            expectedProperty: 'profile',
            expectedNullSafe: true,
        );

        self::assertInstanceOf(PropertyAccessNode::class, $node->accessor);

        $this->assertIdentifierNode(
            node: $node->accessor->accessor,
            expectedName: 'user',
        );
    }
}
