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
use Tuxxedo\View\Lumi\Lexer\Expression\ExpressionLexer;
use Tuxxedo\View\Lumi\Lexer\TokenStream;
use Tuxxedo\View\Lumi\Parser\Handler\IncludeParserHandler;
use Tuxxedo\View\Lumi\Parser\Parser;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Syntax\Node\ArrayNode;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\IncludeNode;
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;
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

    /**
     * @return NodeInterface[]
     */
    private function parseInclude(
        string $arguments,
        bool $braceless = false,
    ): array {
        return $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new IncludeToken(
                        line: 1,
                        op1: $braceless
                            ? 'braceless'
                            : null,
                    ),
                    ...new ExpressionLexer()->lex(
                        startingLine: 1,
                        operand: 'include(' . $arguments . ')',
                    ),
                    new EndToken(
                        line: 1,
                    ),
                ],
            ),
        );
    }

    public function testParsesIncludeWithNamedFile(): void
    {
        $nodes = $this->parseInclude('file: \'partials/header\'');

        self::assertCount(1, $nodes);
        self::assertInstanceOf(IncludeNode::class, $nodes[0]);

        $this->assertLiteralNode(
            node: $nodes[0]->file,
            expectedOperand: 'partials/header',
            expectedType: Type::STRING,
        );

        self::assertNull($nodes[0]->scope);
    }

    public function testParsesIncludeWithPositionalFileAndNamedScope(): void
    {
        $nodes = $this->parseInclude('\'partials/header\', scope: [title: page]');

        self::assertInstanceOf(IncludeNode::class, $nodes[0]);

        $this->assertLiteralNode(
            node: $nodes[0]->file,
            expectedOperand: 'partials/header',
            expectedType: Type::STRING,
        );

        self::assertInstanceOf(ArrayNode::class, $nodes[0]->scope);
    }

    public function testParsesIncludeWithNamedArgumentsInAnyOrder(): void
    {
        $nodes = $this->parseInclude('scope: [], file: name');

        self::assertInstanceOf(IncludeNode::class, $nodes[0]);
        self::assertInstanceOf(IdentifierNode::class, $nodes[0]->file);
        self::assertInstanceOf(ArrayNode::class, $nodes[0]->scope);
    }

    public function testNamedIncludeArgumentsProduceSameNodeAsPositional(): void
    {
        self::assertEquals(
            $this->parseInclude('\'partials/header\', [title: page]'),
            $this->parseInclude('file: \'partials/header\', scope: [title: page]'),
        );
    }

    public function testThrowsOnUnknownNamedIncludeArgument(): void
    {
        self::expectException(ParserException::class);
        self::expectExceptionMessage('Unknown include argument "path" on line 1, expected "file" or "scope"');

        $this->parseInclude('path: \'partials/header\'');
    }

    public function testThrowsOnNamedIncludeArgumentAlreadyPassedPositionally(): void
    {
        self::expectException(ParserException::class);
        self::expectExceptionMessage('Include argument "file" is passed more than once on line 1');

        $this->parseInclude('\'partials/header\', file: \'partials/footer\'');
    }

    public function testThrowsOnIncludeWithOnlyNamedScope(): void
    {
        self::expectException(ParserException::class);
        self::expectExceptionMessage('Include statements must have a file argument on line 1');

        $this->parseInclude('scope: []');
    }

    public function testThrowsWhenNamedScopeIsNotArray(): void
    {
        self::expectException(ParserException::class);
        self::expectExceptionMessage('Include scope must be an array on line 1');

        $this->parseInclude('file: \'partials/header\', scope: page');
    }

    public function testThrowsOnBracelessWithNamedArgument(): void
    {
        self::expectException(ParserException::class);
        self::expectExceptionMessage('Include statements with named arguments must be wrapped in parentheses on line 1');

        $this->parseInclude(
            arguments: 'file: \'partials/header\'',
            braceless: true,
        );
    }
}
