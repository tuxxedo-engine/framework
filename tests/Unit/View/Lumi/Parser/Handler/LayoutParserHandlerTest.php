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

namespace Unit\View\Lumi\Parser\Handler;

use PHPUnit\Framework\TestCase;
use Support\View\Lumi\Parser\NodeAssertionsTrait;
use Tuxxedo\View\Lumi\Lexer\TokenStream;
use Tuxxedo\View\Lumi\Parser\Handler\LayoutParserHandler;
use Tuxxedo\View\Lumi\Parser\Parser;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Syntax\Token\BlockToken;
use Tuxxedo\View\Lumi\Syntax\Token\CommentToken;
use Tuxxedo\View\Lumi\Syntax\Token\EchoToken;
use Tuxxedo\View\Lumi\Syntax\Token\EndBlockToken;
use Tuxxedo\View\Lumi\Syntax\Token\LayoutToken;
use Tuxxedo\View\Lumi\Syntax\Token\TextToken;

class LayoutParserHandlerTest extends TestCase
{
    use NodeAssertionsTrait;

    private LayoutParserHandler $handler;
    private Parser $parser;

    protected function setUp(): void
    {
        $this->handler = new LayoutParserHandler();
        $this->parser = Parser::createWithoutDefaultHandlers();

        $this->parser->state->pushState();
    }

    public function testParsesLayoutWithFile(): void
    {
        $nodes = $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new LayoutToken(
                        line: 1,
                        op1: 'layouts/base.lumi',
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertLayoutNode(
            node: $nodes[0],
            expectedFile: 'layouts/base.lumi',
        );
    }

    public function testParsesLayoutWithCommentsAtRoot(): void
    {
        $nodes = $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new LayoutToken(
                        line: 1,
                        op1: 'layouts/base.lumi',
                    ),
                    new CommentToken(
                        line: 2,
                        op1: 'a note',
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertLayoutNode(
            node: $nodes[0],
            expectedFile: 'layouts/base.lumi',
        );
    }

    public function testParsesLayoutWithBlockContainingArbitraryContent(): void
    {
        $nodes = $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new LayoutToken(
                        line: 1,
                        op1: 'layouts/base.lumi',
                    ),
                    new BlockToken(
                        line: 2,
                        op1: 'content',
                    ),
                    new TextToken(
                        line: 3,
                        op1: 'inside the block',
                    ),
                    new EchoToken(
                        line: 4,
                    ),
                    new EndBlockToken(
                        line: 5,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertLayoutNode(
            node: $nodes[0],
            expectedFile: 'layouts/base.lumi',
        );
    }

    public function testParsesLayoutWithWhitespaceTextAtRoot(): void
    {
        $nodes = $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new LayoutToken(
                        line: 1,
                        op1: 'layouts/base.lumi',
                    ),
                    new TextToken(
                        line: 2,
                        op1: "   \n\t  ",
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertLayoutNode(
            node: $nodes[0],
            expectedFile: 'layouts/base.lumi',
        );
    }

    public function testThrowsWhenNotAtRootScope(): void
    {
        $this->parser->state->pushState();

        self::expectException(ParserException::class);

        $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new LayoutToken(
                        line: 1,
                        op1: 'layouts/base.lumi',
                    ),
                ],
            ),
        );
    }

    public function testThrowsOnMultipleLayouts(): void
    {
        self::expectException(ParserException::class);

        $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new LayoutToken(
                        line: 1,
                        op1: 'layouts/base.lumi',
                    ),
                    new LayoutToken(
                        line: 2,
                        op1: 'layouts/other.lumi',
                    ),
                ],
            ),
        );
    }

    public function testThrowsOnRootLevelNonBlockContent(): void
    {
        self::expectException(ParserException::class);

        $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new LayoutToken(
                        line: 1,
                        op1: 'layouts/base.lumi',
                    ),
                    new EchoToken(
                        line: 2,
                    ),
                ],
            ),
        );
    }

    public function testThrowsOnRootLevelNonWhitespaceText(): void
    {
        self::expectException(ParserException::class);

        $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new LayoutToken(
                        line: 1,
                        op1: 'layouts/base.lumi',
                    ),
                    new TextToken(
                        line: 2,
                        op1: 'visible content',
                    ),
                ],
            ),
        );
    }
}
