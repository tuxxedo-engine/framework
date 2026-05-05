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
use Tuxxedo\View\Lumi\Parser\Handler\LumiParserHandler;
use Tuxxedo\View\Lumi\Parser\Parser;
use Tuxxedo\View\Lumi\Syntax\Token\LumiToken;

class LumiParserHandlerTest extends TestCase
{
    use NodeAssertionsTrait;

    private LumiParserHandler $handler;
    private Parser $parser;

    protected function setUp(): void
    {
        $this->handler = new LumiParserHandler();
        $this->parser = Parser::createWithoutDefaultHandlers();
    }

    public function testTokenClassNameIsLumiToken(): void
    {
        self::assertSame(
            LumiToken::class,
            $this->handler->tokenClassName,
        );
    }

    public function testParsesLumiTokenIntoLumiNode(): void
    {
        $nodes = $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new LumiToken(
                        line: 1,
                        op1: 'github-dark',
                        op2: '{% if user %}hi{% endif %}',
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertLumiNode(
            node: $nodes[0],
            expectedTheme: 'github-dark',
            expectedSourceCode: '{% if user %}hi{% endif %}',
        );
    }
}
