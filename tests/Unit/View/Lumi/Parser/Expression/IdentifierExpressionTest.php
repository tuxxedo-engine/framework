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
use Tuxxedo\View\Lumi\Parser\ParserException;

class IdentifierExpressionTest extends TestCase
{
    use NodeAssertionsTrait;

    private ExpressionParserHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new ExpressionParserHelper();
    }

    public function testParsesBareIdentifier(): void
    {
        $node = $this->helper->parse('foo');

        $this->assertIdentifierNode(
            node: $node,
            expectedName: 'foo',
        );
    }

    public function testParsesIdentifierWithUnderscore(): void
    {
        $node = $this->helper->parse('foo_bar');

        $this->assertIdentifierNode(
            node: $node,
            expectedName: 'foo_bar',
        );
    }

    public function testParsesIdentifierWithDigits(): void
    {
        $node = $this->helper->parse('foo123');

        $this->assertIdentifierNode(
            node: $node,
            expectedName: 'foo123',
        );
    }

    public function testParsesIdentifierWithMixedCharacters(): void
    {
        $node = $this->helper->parse('foo_bar_123');

        $this->assertIdentifierNode(
            node: $node,
            expectedName: 'foo_bar_123',
        );
    }

    public function testParsesSingleCharacterIdentifier(): void
    {
        $node = $this->helper->parse('a');

        $this->assertIdentifierNode(
            node: $node,
            expectedName: 'a',
        );
    }

    public function testThrowsOnTrailingIdentifier(): void
    {
        $this->expectException(ParserException::class);

        $this->helper->parse('foo bar');
    }
}
