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
use Tuxxedo\View\Lumi\Parser\Handler\CommentParserHandler;
use Tuxxedo\View\Lumi\Parser\Parser;
use Tuxxedo\View\Lumi\Syntax\Token\CommentToken;
use Tuxxedo\View\Lumi\Syntax\Token\TextToken;

class CommentParserHandlerTest extends TestCase
{
    use NodeAssertionsTrait;

    private CommentParserHandler $handler;
    private Parser $parser;

    protected function setUp(): void
    {
        $this->handler = new CommentParserHandler();
        $this->parser = Parser::createWithoutDefaultHandlers();
    }

    public function testParsesSingleCommentToken(): void
    {
        $nodes = $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new CommentToken(
                        line: 1,
                        op1: 'a note',
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertCommentNode(
            node: $nodes[0],
            expectedText: 'a note',
        );
    }

    public function testParsesConsecutiveCommentTokens(): void
    {
        $nodes = $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new CommentToken(
                        line: 1,
                        op1: 'first',
                    ),
                    new CommentToken(
                        line: 2,
                        op1: 'second',
                    ),
                    new CommentToken(
                        line: 3,
                        op1: 'third',
                    ),
                ],
            ),
        );

        self::assertCount(3, $nodes);

        $this->assertCommentNode(
            node: $nodes[0],
            expectedText: 'first',
        );

        $this->assertCommentNode(
            node: $nodes[1],
            expectedText: 'second',
        );

        $this->assertCommentNode(
            node: $nodes[2],
            expectedText: 'third',
        );
    }

    public function testStopsAtNonCommentToken(): void
    {
        $stream = new TokenStream(
            tokens: [
                new CommentToken(
                    line: 1,
                    op1: 'note',
                ),
                new TextToken(
                    line: 2,
                    op1: 'after',
                ),
            ],
        );

        $nodes = $this->handler->parse(
            parser: $this->parser,
            stream: $stream,
        );

        self::assertCount(1, $nodes);

        $this->assertCommentNode(
            node: $nodes[0],
            expectedText: 'note',
        );

        self::assertFalse($stream->eof());
        self::assertInstanceOf(TextToken::class, $stream->current());
    }
}
