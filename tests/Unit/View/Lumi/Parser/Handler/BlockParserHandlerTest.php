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
use Tuxxedo\View\Lumi\Parser\Handler\BlockParserHandler;
use Tuxxedo\View\Lumi\Parser\Handler\TextParserHandler;
use Tuxxedo\View\Lumi\Parser\Parser;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Syntax\Node\BlockNode;
use Tuxxedo\View\Lumi\Syntax\Token\BlockToken;
use Tuxxedo\View\Lumi\Syntax\Token\EndBlockToken;
use Tuxxedo\View\Lumi\Syntax\Token\TextToken;

class BlockParserHandlerTest extends TestCase
{
    use NodeAssertionsTrait;

    private BlockParserHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new BlockParserHandler();
    }

    public function testParsesEmptyBlock(): void
    {
        $parser = Parser::createWithoutDefaultHandlers();

        $nodes = $this->handler->parse(
            parser: $parser,
            stream: new TokenStream(
                tokens: [
                    new BlockToken(
                        line: 1,
                        op1: 'sidebar',
                    ),
                    new EndBlockToken(
                        line: 1,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertBlockNode(
            node: $nodes[0],
            expectedName: 'sidebar',
            expectedBodyCount: 0,
        );
    }

    public function testParsesBlockWithTextBody(): void
    {
        $parser = Parser::createWithoutDefaultHandlers(
            handlers: [
                new TextParserHandler(),
            ],
        );

        $nodes = $this->handler->parse(
            parser: $parser,
            stream: new TokenStream(
                tokens: [
                    new BlockToken(
                        line: 1,
                        op1: 'sidebar',
                    ),
                    new TextToken(
                        line: 1,
                        op1: 'inside',
                    ),
                    new EndBlockToken(
                        line: 1,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertBlockNode(
            node: $nodes[0],
            expectedName: 'sidebar',
            expectedBodyCount: 1,
        );

        self::assertInstanceOf(BlockNode::class, $nodes[0]);

        $this->assertTextNode(
            node: $nodes[0]->body[0],
            expectedText: 'inside',
        );
    }

    public function testThrowsOnNestedBlocks(): void
    {
        $parser = Parser::createWithoutDefaultHandlers();

        self::expectException(ParserException::class);

        $this->handler->parse(
            parser: $parser,
            stream: new TokenStream(
                tokens: [
                    new BlockToken(
                        line: 1,
                        op1: 'outer',
                    ),
                    new BlockToken(
                        line: 2,
                        op1: 'inner',
                    ),
                    new EndBlockToken(
                        line: 3,
                    ),
                    new EndBlockToken(
                        line: 4,
                    ),
                ],
            ),
        );
    }

    public function testConsumesEndBlockTokenAndStopsAtFollowingToken(): void
    {
        $parser = Parser::createWithoutDefaultHandlers();

        $stream = new TokenStream(
            tokens: [
                new BlockToken(
                    line: 1,
                    op1: 'sidebar',
                ),
                new EndBlockToken(
                    line: 1,
                ),
                new TextToken(
                    line: 2,
                    op1: 'after',
                ),
            ],
        );

        $this->handler->parse(
            parser: $parser,
            stream: $stream,
        );

        self::assertFalse($stream->eof());
        self::assertInstanceOf(TextToken::class, $stream->current());
        self::assertSame('after', $stream->current()->op1);
    }
}
