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
use Tuxxedo\View\Lumi\Parser\Handler\BreakParserHandler;
use Tuxxedo\View\Lumi\Parser\Parser;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Syntax\Token\BreakToken;

class BreakParserHandlerTest extends TestCase
{
    use NodeAssertionsTrait;

    private BreakParserHandler $handler;
    private Parser $parser;

    protected function setUp(): void
    {
        $this->handler = new BreakParserHandler();
        $this->parser = Parser::createWithoutDefaultHandlers();
    }

    public function testParsesBreakInsideLoop(): void
    {
        $this->parser->state->enterLoop();

        $nodes = $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new BreakToken(
                        line: 1,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertBreakNode(
            node: $nodes[0],
        );
    }

    public function testParsesBreakWithCount(): void
    {
        $this->parser->state->enterLoop();
        $this->parser->state->enterLoop();

        $nodes = $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new BreakToken(
                        line: 1,
                        op1: '2',
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertBreakNode(
            node: $nodes[0],
            expectedCount: 2,
        );
    }

    public function testThrowsWhenBreakIsOutsideLoop(): void
    {
        self::expectException(ParserException::class);

        $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new BreakToken(
                        line: 1,
                    ),
                ],
            ),
        );
    }

    public function testThrowsWhenBreakCountExceedsLoopDepth(): void
    {
        $this->parser->state->enterLoop();

        self::expectException(ParserException::class);

        $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new BreakToken(
                        line: 1,
                        op1: '3',
                    ),
                ],
            ),
        );
    }
}
