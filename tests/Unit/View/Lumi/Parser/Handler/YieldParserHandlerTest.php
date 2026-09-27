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
use Tuxxedo\View\Lumi\Parser\Handler\YieldParserHandler;
use Tuxxedo\View\Lumi\Parser\Parser;
use Tuxxedo\View\Lumi\Syntax\Token\YieldToken;

class YieldParserHandlerTest extends TestCase
{
    use NodeAssertionsTrait;

    private YieldParserHandler $handler;
    private Parser $parser;

    protected function setUp(): void
    {
        $this->handler = new YieldParserHandler();
        $this->parser = Parser::createWithoutDefaultHandlers();

        $this->parser->state->pushState();
    }

    public function testParsesYieldToken(): void
    {
        $nodes = $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new YieldToken(
                        line: 1,
                        op1: 'main',
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertYieldNode(
            node: $nodes[0],
            expectedName: 'main',
        );
    }

    public function testParsesYieldTokenPreservingBlockName(): void
    {
        $nodes = $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new YieldToken(
                        line: 7,
                        op1: 'sidebar',
                    ),
                ],
            ),
        );

        $this->assertYieldNode(
            node: $nodes[0],
            expectedName: 'sidebar',
        );
    }

    public function testTokenClassNameIsYieldToken(): void
    {
        self::assertSame(
            YieldToken::class,
            $this->handler->tokenClassName,
        );
    }
}
