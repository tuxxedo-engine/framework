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
use Tuxxedo\View\Lumi\Parser\Handler\ContinueParserHandler;
use Tuxxedo\View\Lumi\Parser\Parser;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Syntax\Token\ContinueToken;

class ContinueParserHandlerTest extends TestCase
{
    use NodeAssertionsTrait;

    private ContinueParserHandler $handler;
    private Parser $parser;

    protected function setUp(): void
    {
        $this->handler = new ContinueParserHandler();
        $this->parser = Parser::createWithoutDefaultHandlers();
    }

    public function testParsesContinueInsideLoop(): void
    {
        $this->parser->state->enterLoop();

        $nodes = $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new ContinueToken(
                        line: 1,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertContinueNode(
            node: $nodes[0],
        );
    }

    public function testParsesContinueWithCount(): void
    {
        $this->parser->state->enterLoop();
        $this->parser->state->enterLoop();

        $nodes = $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new ContinueToken(
                        line: 1,
                        op1: '2',
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertContinueNode(
            node: $nodes[0],
            expectedCount: 2,
        );
    }

    public function testThrowsWhenContinueIsOutsideLoop(): void
    {
        self::expectException(ParserException::class);

        $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new ContinueToken(
                        line: 1,
                    ),
                ],
            ),
        );
    }

    public function testThrowsWhenContinueCountExceedsLoopDepth(): void
    {
        $this->parser->state->enterLoop();

        self::expectException(ParserException::class);

        $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new ContinueToken(
                        line: 1,
                        op1: '3',
                    ),
                ],
            ),
        );
    }
}
