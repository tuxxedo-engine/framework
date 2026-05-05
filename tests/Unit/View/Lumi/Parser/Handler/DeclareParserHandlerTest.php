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
use Tuxxedo\View\Lumi\Parser\Handler\DeclareParserHandler;
use Tuxxedo\View\Lumi\Parser\Parser;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Syntax\Token\DeclareToken;
use Tuxxedo\View\Lumi\Syntax\Token\EndToken;
use Tuxxedo\View\Lumi\Syntax\Token\LiteralToken;
use Tuxxedo\View\Lumi\Syntax\Type;

class DeclareParserHandlerTest extends TestCase
{
    use NodeAssertionsTrait;

    private DeclareParserHandler $handler;
    private Parser $parser;

    protected function setUp(): void
    {
        $this->handler = new DeclareParserHandler();
        $this->parser = Parser::createWithoutDefaultHandlers();

        $this->parser->state->pushState();
    }

    public function testTokenClassNameIsDeclareToken(): void
    {
        self::assertSame(
            DeclareToken::class,
            $this->handler->tokenClassName,
        );
    }

    public function testParsesDeclareWithStringValue(): void
    {
        $nodes = $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new DeclareToken(
                        line: 1,
                        op1: 'theme',
                    ),
                    new LiteralToken(
                        line: 1,
                        op1: 'darkmode',
                        op2: Type::STRING->name,
                    ),
                    new EndToken(
                        line: 1,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertDeclareNode(
            node: $nodes[0],
            expectedDirective: 'theme',
            expectedValue: 'darkmode',
            expectedValueType: Type::STRING,
        );
    }

    public function testParsesDeclareWithIntValue(): void
    {
        $nodes = $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new DeclareToken(
                        line: 1,
                        op1: 'maxRetries',
                    ),
                    new LiteralToken(
                        line: 1,
                        op1: '5',
                        op2: Type::INT->name,
                    ),
                    new EndToken(
                        line: 1,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertDeclareNode(
            node: $nodes[0],
            expectedDirective: 'maxRetries',
            expectedValue: '5',
            expectedValueType: Type::INT,
        );
    }

    public function testThrowsOnNestedDeclare(): void
    {
        $this->parser->state->pushState();

        self::expectException(ParserException::class);

        $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new DeclareToken(
                        line: 1,
                        op1: 'theme',
                    ),
                    new LiteralToken(
                        line: 1,
                        op1: 'darkmode',
                        op2: Type::STRING->name,
                    ),
                    new EndToken(
                        line: 1,
                    ),
                ],
            ),
        );
    }

    public function testThrowsOnUnknownLiteralValueType(): void
    {
        self::expectException(ParserException::class);

        $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new DeclareToken(
                        line: 1,
                        op1: 'theme',
                    ),
                    new LiteralToken(
                        line: 1,
                        op1: 'darkmode',
                        op2: 'GIBBERISH',
                    ),
                    new EndToken(
                        line: 1,
                    ),
                ],
            ),
        );
    }
}
