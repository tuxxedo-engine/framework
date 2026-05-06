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
use Tuxxedo\View\Lumi\Parser\Handler\EchoParserHandler;
use Tuxxedo\View\Lumi\Parser\Parser;
use Tuxxedo\View\Lumi\Syntax\Node\EchoNode;
use Tuxxedo\View\Lumi\Syntax\Operator\BinarySymbol;
use Tuxxedo\View\Lumi\Syntax\TextContext;
use Tuxxedo\View\Lumi\Syntax\Token\EchoToken;
use Tuxxedo\View\Lumi\Syntax\Token\EndToken;
use Tuxxedo\View\Lumi\Syntax\Token\IdentifierToken;
use Tuxxedo\View\Lumi\Syntax\Token\OperatorToken;

class EchoParserHandlerTest extends TestCase
{
    use NodeAssertionsTrait;

    private EchoParserHandler $handler;
    private Parser $parser;

    protected function setUp(): void
    {
        $this->handler = new EchoParserHandler();
        $this->parser = Parser::createWithoutDefaultHandlers();
    }

    public function testParsesEchoOfSingleIdentifier(): void
    {
        $nodes = $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new EchoToken(
                        line: 1,
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'user',
                    ),
                    new EndToken(
                        line: 1,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertEchoNode(
            node: $nodes[0],
        );

        self::assertInstanceOf(EchoNode::class, $nodes[0]);

        $this->assertIdentifierNode(
            node: $nodes[0]->operand,
            expectedName: 'user',
        );
    }

    public function testParsesEchoOfBinaryExpression(): void
    {
        $nodes = $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new EchoToken(
                        line: 1,
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'a',
                    ),
                    new OperatorToken(
                        line: 1,
                        op1: BinarySymbol::ADD->symbol(),
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'b',
                    ),
                    new EndToken(
                        line: 1,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertEchoNode(
            node: $nodes[0],
        );

        self::assertInstanceOf(EchoNode::class, $nodes[0]);

        $this->assertBinaryOpNode(
            node: $nodes[0]->operand,
            expectedOperator: BinarySymbol::ADD,
        );
    }

    public function testParsesEchoWithRawContext(): void
    {
        $nodes = $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new EchoToken(
                        line: 1,
                        op1: TextContext::RAW->name,
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'payload',
                    ),
                    new EndToken(
                        line: 1,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertEchoNode(
            node: $nodes[0],
            expectedContext: TextContext::RAW,
        );
    }

    public function testParsesEchoWithDefaultContextWhenOp1IsNull(): void
    {
        $nodes = $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new EchoToken(
                        line: 1,
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'value',
                    ),
                    new EndToken(
                        line: 1,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertEchoNode(
            node: $nodes[0],
        );
    }

    public function testConsumesUpToAndIncludingEndToken(): void
    {
        $stream = new TokenStream(
            tokens: [
                new EchoToken(
                    line: 1,
                ),
                new IdentifierToken(
                    line: 1,
                    op1: 'value',
                ),
                new EndToken(
                    line: 1,
                ),
                new IdentifierToken(
                    line: 2,
                    op1: 'after',
                ),
            ],
        );

        $this->handler->parse(
            parser: $this->parser,
            stream: $stream,
        );

        self::assertFalse($stream->eof());
        self::assertInstanceOf(IdentifierToken::class, $stream->current());
        self::assertSame('after', $stream->current()->op1);
    }
}
