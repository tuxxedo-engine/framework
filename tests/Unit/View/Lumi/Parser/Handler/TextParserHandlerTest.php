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
use Tuxxedo\View\Lumi\Parser\Handler\TextParserHandler;
use Tuxxedo\View\Lumi\Parser\Parser;
use Tuxxedo\View\Lumi\Syntax\TextContext;
use Tuxxedo\View\Lumi\Syntax\Token\CommentToken;
use Tuxxedo\View\Lumi\Syntax\Token\TextToken;

class TextParserHandlerTest extends TestCase
{
    use NodeAssertionsTrait;

    private TextParserHandler $handler;
    private Parser $parser;

    protected function setUp(): void
    {
        $this->handler = new TextParserHandler();
        $this->parser = Parser::createWithoutDefaultHandlers();
    }

    public function testParsesSingleTextToken(): void
    {
        $nodes = $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new TextToken(
                        line: 1,
                        op1: 'hello world',
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertTextNode(
            node: $nodes[0],
            expectedText: 'hello world',
        );
    }

    public function testParsesConsecutiveTextTokens(): void
    {
        $nodes = $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new TextToken(
                        line: 1,
                        op1: 'first',
                    ),
                    new TextToken(
                        line: 2,
                        op1: 'second',
                    ),
                ],
            ),
        );

        self::assertCount(2, $nodes);

        $this->assertTextNode(
            node: $nodes[0],
            expectedText: 'first',
        );

        $this->assertTextNode(
            node: $nodes[1],
            expectedText: 'second',
        );
    }

    public function testParsesTextTokenWithRawContext(): void
    {
        $nodes = $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new TextToken(
                        line: 1,
                        op1: 'raw payload',
                        op2: TextContext::RAW->name,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertTextNode(
            node: $nodes[0],
            expectedText: 'raw payload',
            expectedContext: TextContext::RAW,
        );
    }

    public function testParsesTextTokenWithDefaultContextWhenOp2IsNull(): void
    {
        $nodes = $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new TextToken(
                        line: 1,
                        op1: 'plain payload',
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertTextNode(
            node: $nodes[0],
            expectedText: 'plain payload',
        );
    }

    public function testStopsAtNonTextToken(): void
    {
        $stream = new TokenStream(
            tokens: [
                new TextToken(
                    line: 1,
                    op1: 'before',
                ),
                new CommentToken(
                    line: 2,
                    op1: 'note',
                ),
            ],
        );

        $nodes = $this->handler->parse(
            parser: $this->parser,
            stream: $stream,
        );

        self::assertCount(1, $nodes);

        $this->assertTextNode(
            node: $nodes[0],
            expectedText: 'before',
        );

        self::assertFalse($stream->eof());
        self::assertInstanceOf(CommentToken::class, $stream->current());
    }
}
