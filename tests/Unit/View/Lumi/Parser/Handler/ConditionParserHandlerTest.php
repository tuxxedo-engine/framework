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
use Tuxxedo\View\Lumi\Parser\Handler\ConditionParserHandler;
use Tuxxedo\View\Lumi\Parser\Handler\TextParserHandler;
use Tuxxedo\View\Lumi\Parser\Parser;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Syntax\Node\ConditionalNode;
use Tuxxedo\View\Lumi\Syntax\Token\ElseIfToken;
use Tuxxedo\View\Lumi\Syntax\Token\ElseToken;
use Tuxxedo\View\Lumi\Syntax\Token\EndIfToken;
use Tuxxedo\View\Lumi\Syntax\Token\EndToken;
use Tuxxedo\View\Lumi\Syntax\Token\IdentifierToken;
use Tuxxedo\View\Lumi\Syntax\Token\IfToken;
use Tuxxedo\View\Lumi\Syntax\Token\TextToken;
use Tuxxedo\View\Lumi\Syntax\Token\TokenInterface;

class ConditionParserHandlerTest extends TestCase
{
    use NodeAssertionsTrait;

    private ConditionParserHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new ConditionParserHandler();
    }

    /**
     * @param class-string<IfToken|ElseIfToken> $tokenClassName
     * @return TokenInterface[]
     */
    private function header(
        string $tokenClassName,
        string $condition,
        int $line,
    ): array {
        return [
            new $tokenClassName(
                line: $line,
            ),
            new IdentifierToken(
                line: $line,
                op1: $condition,
            ),
            new EndToken(
                line: $line,
            ),
        ];
    }

    private function text(
        string $text,
        int $line,
    ): TextToken {
        return new TextToken(
            line: $line,
            op1: $text,
        );
    }

    private function nestingParser(): Parser
    {
        return Parser::createWithoutDefaultHandlers(
            handlers: [
                new TextParserHandler(),
                new ConditionParserHandler(),
            ],
        );
    }

    public function testParsesIfWithBody(): void
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
                    new IfToken(
                        line: 1,
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'user',
                    ),
                    new EndToken(
                        line: 1,
                    ),
                    new TextToken(
                        line: 2,
                        op1: 'hello',
                    ),
                    new EndIfToken(
                        line: 3,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertConditionalNode(
            node: $nodes[0],
            expectedBodyCount: 1,
            expectedBranchCount: 0,
            expectedElseCount: 0,
        );

        self::assertInstanceOf(ConditionalNode::class, $nodes[0]);

        $this->assertIdentifierNode(
            node: $nodes[0]->operand,
            expectedName: 'user',
        );

        $this->assertTextNode(
            node: $nodes[0]->body[0],
            expectedText: 'hello',
        );
    }

    public function testParsesIfWithElseBranch(): void
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
                    new IfToken(
                        line: 1,
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'user',
                    ),
                    new EndToken(
                        line: 1,
                    ),
                    new TextToken(
                        line: 2,
                        op1: 'A',
                    ),
                    new ElseToken(
                        line: 3,
                    ),
                    new TextToken(
                        line: 4,
                        op1: 'B',
                    ),
                    new EndIfToken(
                        line: 5,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertConditionalNode(
            node: $nodes[0],
            expectedBodyCount: 1,
            expectedBranchCount: 0,
            expectedElseCount: 1,
        );

        self::assertInstanceOf(ConditionalNode::class, $nodes[0]);

        $this->assertTextNode(
            node: $nodes[0]->body[0],
            expectedText: 'A',
        );

        $this->assertTextNode(
            node: $nodes[0]->else[0],
            expectedText: 'B',
        );
    }

    public function testParsesIfWithElseIfBranch(): void
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
                    new IfToken(
                        line: 1,
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'a',
                    ),
                    new EndToken(
                        line: 1,
                    ),
                    new TextToken(
                        line: 2,
                        op1: 'A',
                    ),
                    new ElseIfToken(
                        line: 3,
                    ),
                    new IdentifierToken(
                        line: 3,
                        op1: 'b',
                    ),
                    new EndToken(
                        line: 3,
                    ),
                    new TextToken(
                        line: 4,
                        op1: 'B',
                    ),
                    new EndIfToken(
                        line: 5,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertConditionalNode(
            node: $nodes[0],
            expectedBodyCount: 1,
            expectedBranchCount: 1,
            expectedElseCount: 0,
        );

        self::assertInstanceOf(ConditionalNode::class, $nodes[0]);

        $this->assertConditionalBranchNode(
            node: $nodes[0]->branches[0],
            expectedBodyCount: 1,
        );

        $this->assertIdentifierNode(
            node: $nodes[0]->branches[0]->operand,
            expectedName: 'b',
        );

        $this->assertTextNode(
            node: $nodes[0]->branches[0]->body[0],
            expectedText: 'B',
        );
    }

    public function testParsesNestedIfBlocks(): void
    {
        $parser = Parser::createWithoutDefaultHandlers(
            handlers: [
                new TextParserHandler(),
                new ConditionParserHandler(),
            ],
        );

        $nodes = $this->handler->parse(
            parser: $parser,
            stream: new TokenStream(
                tokens: [
                    new IfToken(
                        line: 1,
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'a',
                    ),
                    new EndToken(
                        line: 1,
                    ),
                    new IfToken(
                        line: 2,
                    ),
                    new IdentifierToken(
                        line: 2,
                        op1: 'b',
                    ),
                    new EndToken(
                        line: 2,
                    ),
                    new TextToken(
                        line: 3,
                        op1: 'inner',
                    ),
                    new EndIfToken(
                        line: 4,
                    ),
                    new EndIfToken(
                        line: 5,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertConditionalNode(
            node: $nodes[0],
            expectedBodyCount: 1,
            expectedBranchCount: 0,
            expectedElseCount: 0,
        );

        self::assertInstanceOf(ConditionalNode::class, $nodes[0]);

        $this->assertConditionalNode(
            node: $nodes[0]->body[0],
            expectedBodyCount: 1,
            expectedBranchCount: 0,
            expectedElseCount: 0,
        );
    }

    public function testThrowsOnEmptyConditionExpression(): void
    {
        $parser = Parser::createWithoutDefaultHandlers();

        self::expectException(ParserException::class);

        $this->handler->parse(
            parser: $parser,
            stream: new TokenStream(
                tokens: [
                    new IfToken(
                        line: 1,
                    ),
                    new EndToken(
                        line: 1,
                    ),
                    new EndIfToken(
                        line: 2,
                    ),
                ],
            ),
        );
    }

    public function testParsesNestedIfWithElseInsideOuterBody(): void
    {
        $parser = Parser::createWithoutDefaultHandlers(
            handlers: [
                new TextParserHandler(),
                new ConditionParserHandler(),
            ],
        );

        $nodes = $this->handler->parse(
            parser: $parser,
            stream: new TokenStream(
                tokens: [
                    new IfToken(
                        line: 1,
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'outer',
                    ),
                    new EndToken(
                        line: 1,
                    ),
                    new IfToken(
                        line: 2,
                    ),
                    new IdentifierToken(
                        line: 2,
                        op1: 'inner',
                    ),
                    new EndToken(
                        line: 2,
                    ),
                    new TextToken(
                        line: 3,
                        op1: 'A',
                    ),
                    new ElseToken(
                        line: 4,
                    ),
                    new TextToken(
                        line: 5,
                        op1: 'B',
                    ),
                    new EndIfToken(
                        line: 6,
                    ),
                    new EndIfToken(
                        line: 7,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertConditionalNode(
            node: $nodes[0],
            expectedBodyCount: 1,
            expectedBranchCount: 0,
            expectedElseCount: 0,
        );

        self::assertInstanceOf(ConditionalNode::class, $nodes[0]);

        $this->assertIdentifierNode(
            node: $nodes[0]->operand,
            expectedName: 'outer',
        );

        $this->assertConditionalNode(
            node: $nodes[0]->body[0],
            expectedBodyCount: 1,
            expectedBranchCount: 0,
            expectedElseCount: 1,
        );

        self::assertInstanceOf(ConditionalNode::class, $nodes[0]->body[0]);

        $this->assertIdentifierNode(
            node: $nodes[0]->body[0]->operand,
            expectedName: 'inner',
        );

        $this->assertTextNode(
            node: $nodes[0]->body[0]->body[0],
            expectedText: 'A',
        );

        $this->assertTextNode(
            node: $nodes[0]->body[0]->else[0],
            expectedText: 'B',
        );
    }

    public function testParsesNestedIfWithElseIfInsideOuterBody(): void
    {
        $parser = Parser::createWithoutDefaultHandlers(
            handlers: [
                new TextParserHandler(),
                new ConditionParserHandler(),
            ],
        );

        $nodes = $this->handler->parse(
            parser: $parser,
            stream: new TokenStream(
                tokens: [
                    new IfToken(
                        line: 1,
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'outer',
                    ),
                    new EndToken(
                        line: 1,
                    ),
                    new IfToken(
                        line: 2,
                    ),
                    new IdentifierToken(
                        line: 2,
                        op1: 'inner',
                    ),
                    new EndToken(
                        line: 2,
                    ),
                    new TextToken(
                        line: 3,
                        op1: 'A',
                    ),
                    new ElseIfToken(
                        line: 4,
                    ),
                    new IdentifierToken(
                        line: 4,
                        op1: 'otherbranch',
                    ),
                    new EndToken(
                        line: 4,
                    ),
                    new TextToken(
                        line: 5,
                        op1: 'B',
                    ),
                    new EndIfToken(
                        line: 6,
                    ),
                    new EndIfToken(
                        line: 7,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertConditionalNode(
            node: $nodes[0],
            expectedBodyCount: 1,
            expectedBranchCount: 0,
            expectedElseCount: 0,
        );

        self::assertInstanceOf(ConditionalNode::class, $nodes[0]);

        $this->assertIdentifierNode(
            node: $nodes[0]->operand,
            expectedName: 'outer',
        );

        $this->assertConditionalNode(
            node: $nodes[0]->body[0],
            expectedBodyCount: 1,
            expectedBranchCount: 1,
            expectedElseCount: 0,
        );

        self::assertInstanceOf(ConditionalNode::class, $nodes[0]->body[0]);

        $this->assertIdentifierNode(
            node: $nodes[0]->body[0]->operand,
            expectedName: 'inner',
        );

        $this->assertTextNode(
            node: $nodes[0]->body[0]->body[0],
            expectedText: 'A',
        );

        $this->assertConditionalBranchNode(
            node: $nodes[0]->body[0]->branches[0],
            expectedBodyCount: 1,
        );

        $this->assertIdentifierNode(
            node: $nodes[0]->body[0]->branches[0]->operand,
            expectedName: 'otherbranch',
        );

        $this->assertTextNode(
            node: $nodes[0]->body[0]->branches[0]->body[0],
            expectedText: 'B',
        );
    }

    public function testParsesNestedIfWithElseInsideOuterElseBranch(): void
    {
        $parser = Parser::createWithoutDefaultHandlers(
            handlers: [
                new TextParserHandler(),
                new ConditionParserHandler(),
            ],
        );

        $nodes = $this->handler->parse(
            parser: $parser,
            stream: new TokenStream(
                tokens: [
                    new IfToken(
                        line: 1,
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'outer',
                    ),
                    new EndToken(
                        line: 1,
                    ),
                    new TextToken(
                        line: 2,
                        op1: 'X',
                    ),
                    new ElseToken(
                        line: 3,
                    ),
                    new IfToken(
                        line: 4,
                    ),
                    new IdentifierToken(
                        line: 4,
                        op1: 'inner',
                    ),
                    new EndToken(
                        line: 4,
                    ),
                    new TextToken(
                        line: 5,
                        op1: 'A',
                    ),
                    new ElseToken(
                        line: 6,
                    ),
                    new TextToken(
                        line: 7,
                        op1: 'B',
                    ),
                    new EndIfToken(
                        line: 8,
                    ),
                    new EndIfToken(
                        line: 9,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertConditionalNode(
            node: $nodes[0],
            expectedBodyCount: 1,
            expectedBranchCount: 0,
            expectedElseCount: 1,
        );

        self::assertInstanceOf(ConditionalNode::class, $nodes[0]);

        $this->assertIdentifierNode(
            node: $nodes[0]->operand,
            expectedName: 'outer',
        );

        $this->assertTextNode(
            node: $nodes[0]->body[0],
            expectedText: 'X',
        );

        $this->assertConditionalNode(
            node: $nodes[0]->else[0],
            expectedBodyCount: 1,
            expectedBranchCount: 0,
            expectedElseCount: 1,
        );

        self::assertInstanceOf(ConditionalNode::class, $nodes[0]->else[0]);

        $this->assertIdentifierNode(
            node: $nodes[0]->else[0]->operand,
            expectedName: 'inner',
        );

        $this->assertTextNode(
            node: $nodes[0]->else[0]->body[0],
            expectedText: 'A',
        );

        $this->assertTextNode(
            node: $nodes[0]->else[0]->else[0],
            expectedText: 'B',
        );
    }

    public function testParsesNestedIfWithElseIfInsideOuterElseBranch(): void
    {
        $parser = Parser::createWithoutDefaultHandlers(
            handlers: [
                new TextParserHandler(),
                new ConditionParserHandler(),
            ],
        );

        $nodes = $this->handler->parse(
            parser: $parser,
            stream: new TokenStream(
                tokens: [
                    new IfToken(
                        line: 1,
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'outer',
                    ),
                    new EndToken(
                        line: 1,
                    ),
                    new TextToken(
                        line: 2,
                        op1: 'X',
                    ),
                    new ElseToken(
                        line: 3,
                    ),
                    new IfToken(
                        line: 4,
                    ),
                    new IdentifierToken(
                        line: 4,
                        op1: 'inner',
                    ),
                    new EndToken(
                        line: 4,
                    ),
                    new TextToken(
                        line: 5,
                        op1: 'A',
                    ),
                    new ElseIfToken(
                        line: 6,
                    ),
                    new IdentifierToken(
                        line: 6,
                        op1: 'otherbranch',
                    ),
                    new EndToken(
                        line: 6,
                    ),
                    new TextToken(
                        line: 7,
                        op1: 'B',
                    ),
                    new EndIfToken(
                        line: 8,
                    ),
                    new EndIfToken(
                        line: 9,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertConditionalNode(
            node: $nodes[0],
            expectedBodyCount: 1,
            expectedBranchCount: 0,
            expectedElseCount: 1,
        );

        self::assertInstanceOf(ConditionalNode::class, $nodes[0]);

        $this->assertIdentifierNode(
            node: $nodes[0]->operand,
            expectedName: 'outer',
        );

        $this->assertTextNode(
            node: $nodes[0]->body[0],
            expectedText: 'X',
        );

        $this->assertConditionalNode(
            node: $nodes[0]->else[0],
            expectedBodyCount: 1,
            expectedBranchCount: 1,
            expectedElseCount: 0,
        );

        self::assertInstanceOf(ConditionalNode::class, $nodes[0]->else[0]);

        $this->assertIdentifierNode(
            node: $nodes[0]->else[0]->operand,
            expectedName: 'inner',
        );

        $this->assertTextNode(
            node: $nodes[0]->else[0]->body[0],
            expectedText: 'A',
        );

        $this->assertConditionalBranchNode(
            node: $nodes[0]->else[0]->branches[0],
            expectedBodyCount: 1,
        );

        $this->assertIdentifierNode(
            node: $nodes[0]->else[0]->branches[0]->operand,
            expectedName: 'otherbranch',
        );

        $this->assertTextNode(
            node: $nodes[0]->else[0]->branches[0]->body[0],
            expectedText: 'B',
        );
    }

    public function testParsesNestedIfInsideElseBranch(): void
    {
        $parser = Parser::createWithoutDefaultHandlers(
            handlers: [
                new TextParserHandler(),
                new ConditionParserHandler(),
            ],
        );

        $nodes = $this->handler->parse(
            parser: $parser,
            stream: new TokenStream(
                tokens: [
                    new IfToken(
                        line: 1,
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'a',
                    ),
                    new EndToken(
                        line: 1,
                    ),
                    new TextToken(
                        line: 2,
                        op1: 'A',
                    ),
                    new ElseToken(
                        line: 3,
                    ),
                    new IfToken(
                        line: 4,
                    ),
                    new IdentifierToken(
                        line: 4,
                        op1: 'b',
                    ),
                    new EndToken(
                        line: 4,
                    ),
                    new TextToken(
                        line: 5,
                        op1: 'B',
                    ),
                    new EndIfToken(
                        line: 6,
                    ),
                    new TextToken(
                        line: 7,
                        op1: 'C',
                    ),
                    new EndIfToken(
                        line: 8,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertConditionalNode(
            node: $nodes[0],
            expectedBodyCount: 1,
            expectedBranchCount: 0,
            expectedElseCount: 2,
        );

        self::assertInstanceOf(ConditionalNode::class, $nodes[0]);

        $this->assertTextNode(
            node: $nodes[0]->body[0],
            expectedText: 'A',
        );

        $this->assertConditionalNode(
            node: $nodes[0]->else[0],
            expectedBodyCount: 1,
            expectedBranchCount: 0,
            expectedElseCount: 0,
        );

        $this->assertTextNode(
            node: $nodes[0]->else[1],
            expectedText: 'C',
        );
    }

    public function testParsesNestedIfInsideElseIfBranch(): void
    {
        $nodes = $this->handler->parse(
            parser: $this->nestingParser(),
            stream: new TokenStream(
                tokens: [
                    ...$this->header(
                        tokenClassName: IfToken::class,
                        condition: 'a',
                        line: 1,
                    ),
                    $this->text(
                        text: 'A',
                        line: 2,
                    ),
                    ...$this->header(
                        tokenClassName: ElseIfToken::class,
                        condition: 'b',
                        line: 3,
                    ),
                    ...$this->header(
                        tokenClassName: IfToken::class,
                        condition: 'c',
                        line: 4,
                    ),
                    $this->text(
                        text: 'C',
                        line: 5,
                    ),
                    new EndIfToken(
                        line: 6,
                    ),
                    $this->text(
                        text: 'B',
                        line: 7,
                    ),
                    new EndIfToken(
                        line: 8,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertConditionalNode(
            node: $nodes[0],
            expectedBodyCount: 1,
            expectedBranchCount: 1,
            expectedElseCount: 0,
        );

        self::assertInstanceOf(ConditionalNode::class, $nodes[0]);

        $branch = $nodes[0]->branches[0];

        self::assertCount(2, $branch->body);

        $this->assertConditionalNode(
            node: $branch->body[0],
            expectedBodyCount: 1,
            expectedBranchCount: 0,
            expectedElseCount: 0,
        );

        $this->assertTextNode(
            node: $branch->body[1],
            expectedText: 'B',
        );
    }

    public function testParsesNestedIfWithElseAndElseIfInsideElseIfBranch(): void
    {
        $nodes = $this->handler->parse(
            parser: $this->nestingParser(),
            stream: new TokenStream(
                tokens: [
                    ...$this->header(
                        tokenClassName: IfToken::class,
                        condition: 'a',
                        line: 1,
                    ),
                    ...$this->header(
                        tokenClassName: ElseIfToken::class,
                        condition: 'b',
                        line: 2,
                    ),
                    ...$this->header(
                        tokenClassName: IfToken::class,
                        condition: 'c',
                        line: 3,
                    ),
                    $this->text(
                        text: 'C',
                        line: 4,
                    ),
                    ...$this->header(
                        tokenClassName: ElseIfToken::class,
                        condition: 'd',
                        line: 5,
                    ),
                    $this->text(
                        text: 'D',
                        line: 6,
                    ),
                    new ElseToken(
                        line: 7,
                    ),
                    $this->text(
                        text: 'E',
                        line: 8,
                    ),
                    new EndIfToken(
                        line: 9,
                    ),
                    new EndIfToken(
                        line: 10,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertConditionalNode(
            node: $nodes[0],
            expectedBodyCount: 0,
            expectedBranchCount: 1,
            expectedElseCount: 0,
        );

        self::assertInstanceOf(ConditionalNode::class, $nodes[0]);

        $branch = $nodes[0]->branches[0];

        self::assertCount(1, $branch->body);

        $this->assertConditionalNode(
            node: $branch->body[0],
            expectedBodyCount: 1,
            expectedBranchCount: 1,
            expectedElseCount: 1,
        );
    }

    public function testElseAfterElseIfBranchWithNestedIfBelongsToOuterIf(): void
    {
        $nodes = $this->handler->parse(
            parser: $this->nestingParser(),
            stream: new TokenStream(
                tokens: [
                    ...$this->header(
                        tokenClassName: IfToken::class,
                        condition: 'a',
                        line: 1,
                    ),
                    ...$this->header(
                        tokenClassName: ElseIfToken::class,
                        condition: 'b',
                        line: 2,
                    ),
                    ...$this->header(
                        tokenClassName: IfToken::class,
                        condition: 'c',
                        line: 3,
                    ),
                    new EndIfToken(
                        line: 4,
                    ),
                    new ElseToken(
                        line: 5,
                    ),
                    $this->text(
                        text: 'E',
                        line: 6,
                    ),
                    new EndIfToken(
                        line: 7,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertConditionalNode(
            node: $nodes[0],
            expectedBodyCount: 0,
            expectedBranchCount: 1,
            expectedElseCount: 1,
        );

        self::assertInstanceOf(ConditionalNode::class, $nodes[0]);

        $branch = $nodes[0]->branches[0];

        self::assertCount(1, $branch->body);

        $this->assertConditionalNode(
            node: $branch->body[0],
            expectedBodyCount: 0,
            expectedBranchCount: 0,
            expectedElseCount: 0,
        );

        $this->assertTextNode(
            node: $nodes[0]->else[0],
            expectedText: 'E',
        );
    }

    public function testParsesThreeLevelNestedIf(): void
    {
        $nodes = $this->handler->parse(
            parser: $this->nestingParser(),
            stream: new TokenStream(
                tokens: [
                    ...$this->header(
                        tokenClassName: IfToken::class,
                        condition: 'a',
                        line: 1,
                    ),
                    ...$this->header(
                        tokenClassName: IfToken::class,
                        condition: 'b',
                        line: 2,
                    ),
                    ...$this->header(
                        tokenClassName: IfToken::class,
                        condition: 'c',
                        line: 3,
                    ),
                    $this->text(
                        text: 'C',
                        line: 4,
                    ),
                    new EndIfToken(
                        line: 5,
                    ),
                    new EndIfToken(
                        line: 6,
                    ),
                    $this->text(
                        text: 'tail',
                        line: 7,
                    ),
                    new EndIfToken(
                        line: 8,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertConditionalNode(
            node: $nodes[0],
            expectedBodyCount: 2,
            expectedBranchCount: 0,
            expectedElseCount: 0,
        );

        self::assertInstanceOf(ConditionalNode::class, $nodes[0]);

        $middle = $nodes[0]->body[0];

        $this->assertConditionalNode(
            node: $middle,
            expectedBodyCount: 1,
            expectedBranchCount: 0,
            expectedElseCount: 0,
        );

        self::assertInstanceOf(ConditionalNode::class, $middle);

        $this->assertConditionalNode(
            node: $middle->body[0],
            expectedBodyCount: 1,
            expectedBranchCount: 0,
            expectedElseCount: 0,
        );

        $this->assertTextNode(
            node: $nodes[0]->body[1],
            expectedText: 'tail',
        );
    }
}
