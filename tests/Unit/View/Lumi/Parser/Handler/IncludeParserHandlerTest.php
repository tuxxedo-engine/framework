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
use Tuxxedo\View\Lumi\Parser\Handler\IncludeParserHandler;
use Tuxxedo\View\Lumi\Parser\Parser;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Syntax\Node\ArrayNode;
use Tuxxedo\View\Lumi\Syntax\Node\IncludeNode;
use Tuxxedo\View\Lumi\Syntax\Operator\CharacterSymbol;
use Tuxxedo\View\Lumi\Syntax\Token\CharacterToken;
use Tuxxedo\View\Lumi\Syntax\Token\EndToken;
use Tuxxedo\View\Lumi\Syntax\Token\IdentifierToken;
use Tuxxedo\View\Lumi\Syntax\Token\IncludeToken;
use Tuxxedo\View\Lumi\Syntax\Token\LiteralToken;
use Tuxxedo\View\Lumi\Syntax\Type;

class IncludeParserHandlerTest extends TestCase
{
    use NodeAssertionsTrait;

    private IncludeParserHandler $handler;
    private Parser $parser;

    protected function setUp(): void
    {
        $this->handler = new IncludeParserHandler();
        $this->parser = Parser::createWithoutDefaultHandlers();
    }

    public function testParsesSimpleInclude(): void
    {
        $nodes = $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new IncludeToken(
                        line: 1,
                        op1: null,
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'include',
                    ),
                    new CharacterToken(
                        line: 1,
                        op1: CharacterSymbol::LEFT_PARENTHESIS->symbol(),
                    ),
                    new LiteralToken(
                        line: 1,
                        op1: 'partials/header.lumi',
                        op2: Type::STRING->name,
                    ),
                    new CharacterToken(
                        line: 1,
                        op1: CharacterSymbol::RIGHT_PARENTHESIS->symbol(),
                    ),
                    new EndToken(
                        line: 1,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertIncludeNode(
            node: $nodes[0],
        );

        self::assertInstanceOf(IncludeNode::class, $nodes[0]);

        $this->assertLiteralNode(
            node: $nodes[0]->file,
            expectedOperand: 'partials/header.lumi',
            expectedType: Type::STRING,
        );

        self::assertNull($nodes[0]->scope);
    }

    public function testParsesIncludeWithArrayScope(): void
    {
        $nodes = $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new IncludeToken(
                        line: 1,
                        op1: null,
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'include',
                    ),
                    new CharacterToken(
                        line: 1,
                        op1: CharacterSymbol::LEFT_PARENTHESIS->symbol(),
                    ),
                    new LiteralToken(
                        line: 1,
                        op1: 'partials/header.lumi',
                        op2: Type::STRING->name,
                    ),
                    new CharacterToken(
                        line: 1,
                        op1: CharacterSymbol::COMMA->symbol(),
                    ),
                    new CharacterToken(
                        line: 1,
                        op1: CharacterSymbol::LEFT_SQUARE_BRACKET->symbol(),
                    ),
                    new CharacterToken(
                        line: 1,
                        op1: CharacterSymbol::RIGHT_SQUARE_BRACKET->symbol(),
                    ),
                    new CharacterToken(
                        line: 1,
                        op1: CharacterSymbol::RIGHT_PARENTHESIS->symbol(),
                    ),
                    new EndToken(
                        line: 1,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        self::assertInstanceOf(IncludeNode::class, $nodes[0]);
        self::assertInstanceOf(ArrayNode::class, $nodes[0]->scope);
    }

    public function testThrowsOnInvalidIncludeSyntaxWhenExpressionIsNotFunctionCall(): void
    {
        self::expectException(ParserException::class);

        $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new IncludeToken(
                        line: 1,
                        op1: null,
                    ),
                    new LiteralToken(
                        line: 1,
                        op1: 'partials/header.lumi',
                        op2: Type::STRING->name,
                    ),
                    new EndToken(
                        line: 1,
                    ),
                ],
            ),
        );
    }

    public function testThrowsOnInvalidIncludeSyntaxWhenFunctionNameIsNotInclude(): void
    {
        self::expectException(ParserException::class);

        $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new IncludeToken(
                        line: 1,
                        op1: null,
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'render',
                    ),
                    new CharacterToken(
                        line: 1,
                        op1: CharacterSymbol::LEFT_PARENTHESIS->symbol(),
                    ),
                    new LiteralToken(
                        line: 1,
                        op1: 'partials/header.lumi',
                        op2: Type::STRING->name,
                    ),
                    new CharacterToken(
                        line: 1,
                        op1: CharacterSymbol::RIGHT_PARENTHESIS->symbol(),
                    ),
                    new EndToken(
                        line: 1,
                    ),
                ],
            ),
        );
    }

    public function testThrowsOnTooManyArguments(): void
    {
        self::expectException(ParserException::class);

        $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new IncludeToken(
                        line: 1,
                        op1: null,
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'include',
                    ),
                    new CharacterToken(
                        line: 1,
                        op1: CharacterSymbol::LEFT_PARENTHESIS->symbol(),
                    ),
                    new LiteralToken(
                        line: 1,
                        op1: 'a',
                        op2: Type::STRING->name,
                    ),
                    new CharacterToken(
                        line: 1,
                        op1: CharacterSymbol::COMMA->symbol(),
                    ),
                    new CharacterToken(
                        line: 1,
                        op1: CharacterSymbol::LEFT_SQUARE_BRACKET->symbol(),
                    ),
                    new CharacterToken(
                        line: 1,
                        op1: CharacterSymbol::RIGHT_SQUARE_BRACKET->symbol(),
                    ),
                    new CharacterToken(
                        line: 1,
                        op1: CharacterSymbol::COMMA->symbol(),
                    ),
                    new LiteralToken(
                        line: 1,
                        op1: 'extra',
                        op2: Type::STRING->name,
                    ),
                    new CharacterToken(
                        line: 1,
                        op1: CharacterSymbol::RIGHT_PARENTHESIS->symbol(),
                    ),
                    new EndToken(
                        line: 1,
                    ),
                ],
            ),
        );
    }

    public function testThrowsOnBracelessWithMultipleArguments(): void
    {
        self::expectException(ParserException::class);

        $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new IncludeToken(
                        line: 1,
                        op1: 'braceless',
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'include',
                    ),
                    new CharacterToken(
                        line: 1,
                        op1: CharacterSymbol::LEFT_PARENTHESIS->symbol(),
                    ),
                    new LiteralToken(
                        line: 1,
                        op1: 'partials/header.lumi',
                        op2: Type::STRING->name,
                    ),
                    new CharacterToken(
                        line: 1,
                        op1: CharacterSymbol::COMMA->symbol(),
                    ),
                    new CharacterToken(
                        line: 1,
                        op1: CharacterSymbol::LEFT_SQUARE_BRACKET->symbol(),
                    ),
                    new CharacterToken(
                        line: 1,
                        op1: CharacterSymbol::RIGHT_SQUARE_BRACKET->symbol(),
                    ),
                    new CharacterToken(
                        line: 1,
                        op1: CharacterSymbol::RIGHT_PARENTHESIS->symbol(),
                    ),
                    new EndToken(
                        line: 1,
                    ),
                ],
            ),
        );
    }

    public function testThrowsWhenScopeIsNotArray(): void
    {
        self::expectException(ParserException::class);

        $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new IncludeToken(
                        line: 1,
                        op1: null,
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'include',
                    ),
                    new CharacterToken(
                        line: 1,
                        op1: CharacterSymbol::LEFT_PARENTHESIS->symbol(),
                    ),
                    new LiteralToken(
                        line: 1,
                        op1: 'partials/header.lumi',
                        op2: Type::STRING->name,
                    ),
                    new CharacterToken(
                        line: 1,
                        op1: CharacterSymbol::COMMA->symbol(),
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'notAnArray',
                    ),
                    new CharacterToken(
                        line: 1,
                        op1: CharacterSymbol::RIGHT_PARENTHESIS->symbol(),
                    ),
                    new EndToken(
                        line: 1,
                    ),
                ],
            ),
        );
    }
}
