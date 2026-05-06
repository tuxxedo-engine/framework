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
use Tuxxedo\View\Lumi\Parser\Handler\ForParserHandler;
use Tuxxedo\View\Lumi\Parser\Handler\TextParserHandler;
use Tuxxedo\View\Lumi\Parser\Parser;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Syntax\Node\ForNode;
use Tuxxedo\View\Lumi\Syntax\Node\PropertyAccessNode;
use Tuxxedo\View\Lumi\Syntax\Operator\CharacterSymbol;
use Tuxxedo\View\Lumi\Syntax\Token\CharacterToken;
use Tuxxedo\View\Lumi\Syntax\Token\EndForToken;
use Tuxxedo\View\Lumi\Syntax\Token\EndToken;
use Tuxxedo\View\Lumi\Syntax\Token\ForToken;
use Tuxxedo\View\Lumi\Syntax\Token\IdentifierToken;
use Tuxxedo\View\Lumi\Syntax\Token\LiteralToken;
use Tuxxedo\View\Lumi\Syntax\Token\TextToken;
use Tuxxedo\View\Lumi\Syntax\Type;

class ForParserHandlerTest extends TestCase
{
    use NodeAssertionsTrait;

    private ForParserHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new ForParserHandler();
    }

    public function testParsesForLoopOverIterable(): void
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
                    new ForToken(
                        line: 1,
                        op1: 'item',
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'users',
                    ),
                    new CharacterToken(
                        line: 1,
                        op1: CharacterSymbol::DOT->symbol(),
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'items',
                    ),
                    new EndToken(
                        line: 1,
                    ),
                    new TextToken(
                        line: 2,
                        op1: 'inside',
                    ),
                    new EndForToken(
                        line: 3,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertForNode(
            node: $nodes[0],
            expectedValueName: 'item',
            expectedBodyCount: 1,
        );

        self::assertInstanceOf(ForNode::class, $nodes[0]);
        self::assertInstanceOf(PropertyAccessNode::class, $nodes[0]->iterator);

        $this->assertTextNode(
            node: $nodes[0]->body[0],
            expectedText: 'inside',
        );
    }

    public function testParsesForLoopWithKey(): void
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
                    new ForToken(
                        line: 1,
                        op1: 'value',
                        op2: 'key',
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'users',
                    ),
                    new CharacterToken(
                        line: 1,
                        op1: CharacterSymbol::DOT->symbol(),
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'items',
                    ),
                    new EndToken(
                        line: 1,
                    ),
                    new TextToken(
                        line: 2,
                        op1: 'inside',
                    ),
                    new EndForToken(
                        line: 3,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertForNode(
            node: $nodes[0],
            expectedValueName: 'value',
            expectedBodyCount: 1,
            expectedKeyName: 'key',
        );
    }

    public function testThrowsOnEmptyIteratorExpression(): void
    {
        $parser = Parser::createWithoutDefaultHandlers();

        self::expectException(ParserException::class);

        $this->handler->parse(
            parser: $parser,
            stream: new TokenStream(
                tokens: [
                    new ForToken(
                        line: 1,
                        op1: 'item',
                    ),
                    new EndToken(
                        line: 1,
                    ),
                    new EndForToken(
                        line: 2,
                    ),
                ],
            ),
        );
    }

    public function testThrowsOnNonIterableIteratorExpression(): void
    {
        $parser = Parser::createWithoutDefaultHandlers();

        self::expectException(ParserException::class);

        $this->handler->parse(
            parser: $parser,
            stream: new TokenStream(
                tokens: [
                    new ForToken(
                        line: 1,
                        op1: 'item',
                    ),
                    new LiteralToken(
                        line: 1,
                        op1: '5',
                        op2: Type::INT->name,
                    ),
                    new EndToken(
                        line: 1,
                    ),
                    new EndForToken(
                        line: 2,
                    ),
                ],
            ),
        );
    }
}
