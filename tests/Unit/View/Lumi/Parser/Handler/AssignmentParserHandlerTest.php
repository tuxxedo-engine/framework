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
use Tuxxedo\View\Lumi\Parser\Handler\AssignmentParserHandler;
use Tuxxedo\View\Lumi\Parser\Parser;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Syntax\Node\ArrayAccessNode;
use Tuxxedo\View\Lumi\Syntax\Node\AssignmentNode;
use Tuxxedo\View\Lumi\Syntax\Node\PropertyAccessNode;
use Tuxxedo\View\Lumi\Syntax\Operator\AssignmentSymbol;
use Tuxxedo\View\Lumi\Syntax\Operator\BinarySymbol;
use Tuxxedo\View\Lumi\Syntax\Operator\CharacterSymbol;
use Tuxxedo\View\Lumi\Syntax\Token\AssignToken;
use Tuxxedo\View\Lumi\Syntax\Token\CharacterToken;
use Tuxxedo\View\Lumi\Syntax\Token\EndToken;
use Tuxxedo\View\Lumi\Syntax\Token\IdentifierToken;
use Tuxxedo\View\Lumi\Syntax\Token\LiteralToken;
use Tuxxedo\View\Lumi\Syntax\Token\OperatorToken;
use Tuxxedo\View\Lumi\Syntax\Type;

class AssignmentParserHandlerTest extends TestCase
{
    use NodeAssertionsTrait;

    private AssignmentParserHandler $handler;
    private Parser $parser;

    protected function setUp(): void
    {
        $this->handler = new AssignmentParserHandler();
        $this->parser = Parser::createWithoutDefaultHandlers();
    }

    public function testTokenClassNameIsAssignToken(): void
    {
        self::assertSame(
            AssignToken::class,
            $this->handler->tokenClassName,
        );
    }

    public function testParsesSimpleAssignment(): void
    {
        $nodes = $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new AssignToken(
                        line: 1,
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'count',
                    ),
                    new OperatorToken(
                        line: 1,
                        op1: AssignmentSymbol::ASSIGN->symbol(),
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

        $this->assertAssignmentNode(
            node: $nodes[0],
            expectedOperator: AssignmentSymbol::ASSIGN,
        );

        self::assertInstanceOf(AssignmentNode::class, $nodes[0]);

        $this->assertIdentifierNode(
            node: $nodes[0]->name,
            expectedName: 'count',
        );

        $this->assertLiteralNode(
            node: $nodes[0]->value,
            expectedOperand: '5',
            expectedType: Type::INT,
        );
    }

    public function testParsesCompoundAddAssignment(): void
    {
        $nodes = $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new AssignToken(
                        line: 1,
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'count',
                    ),
                    new OperatorToken(
                        line: 1,
                        op1: AssignmentSymbol::ADD->symbol(),
                    ),
                    new LiteralToken(
                        line: 1,
                        op1: '1',
                        op2: Type::INT->name,
                    ),
                    new EndToken(
                        line: 1,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertAssignmentNode(
            node: $nodes[0],
            expectedOperator: AssignmentSymbol::ADD,
        );
    }

    public function testParsesPropertyAccessAssignment(): void
    {
        $nodes = $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new AssignToken(
                        line: 1,
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'user',
                    ),
                    new CharacterToken(
                        line: 1,
                        op1: CharacterSymbol::DOT->symbol(),
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'name',
                    ),
                    new OperatorToken(
                        line: 1,
                        op1: AssignmentSymbol::ASSIGN->symbol(),
                    ),
                    new LiteralToken(
                        line: 1,
                        op1: 'foo',
                        op2: Type::STRING->name,
                    ),
                    new EndToken(
                        line: 1,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        $this->assertAssignmentNode(
            node: $nodes[0],
            expectedOperator: AssignmentSymbol::ASSIGN,
        );

        self::assertInstanceOf(AssignmentNode::class, $nodes[0]);

        $this->assertPropertyAccessNode(
            node: $nodes[0]->name,
            expectedProperty: 'name',
        );

        self::assertInstanceOf(PropertyAccessNode::class, $nodes[0]->name);

        $this->assertIdentifierNode(
            node: $nodes[0]->name->accessor,
            expectedName: 'user',
        );
    }

    public function testParsesNestedPropertyAccessAssignment(): void
    {
        $nodes = $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new AssignToken(
                        line: 1,
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'user',
                    ),
                    new CharacterToken(
                        line: 1,
                        op1: CharacterSymbol::DOT->symbol(),
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'profile',
                    ),
                    new CharacterToken(
                        line: 1,
                        op1: CharacterSymbol::DOT->symbol(),
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'email',
                    ),
                    new OperatorToken(
                        line: 1,
                        op1: AssignmentSymbol::ASSIGN->symbol(),
                    ),
                    new LiteralToken(
                        line: 1,
                        op1: 'a@b',
                        op2: Type::STRING->name,
                    ),
                    new EndToken(
                        line: 1,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        self::assertInstanceOf(AssignmentNode::class, $nodes[0]);

        $this->assertPropertyAccessNode(
            node: $nodes[0]->name,
            expectedProperty: 'email',
        );

        self::assertInstanceOf(PropertyAccessNode::class, $nodes[0]->name);

        $this->assertPropertyAccessNode(
            node: $nodes[0]->name->accessor,
            expectedProperty: 'profile',
        );
    }

    public function testParsesArrayAccessAssignment(): void
    {
        $nodes = $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new AssignToken(
                        line: 1,
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'users',
                    ),
                    new CharacterToken(
                        line: 1,
                        op1: CharacterSymbol::LEFT_SQUARE_BRACKET->symbol(),
                    ),
                    new LiteralToken(
                        line: 1,
                        op1: '0',
                        op2: Type::INT->name,
                    ),
                    new CharacterToken(
                        line: 1,
                        op1: CharacterSymbol::RIGHT_SQUARE_BRACKET->symbol(),
                    ),
                    new OperatorToken(
                        line: 1,
                        op1: AssignmentSymbol::ASSIGN->symbol(),
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

        self::assertInstanceOf(AssignmentNode::class, $nodes[0]);
        self::assertInstanceOf(ArrayAccessNode::class, $nodes[0]->name);

        $this->assertIdentifierNode(
            node: $nodes[0]->name->array,
            expectedName: 'users',
        );

        self::assertNotNull($nodes[0]->name->key);

        $this->assertLiteralNode(
            node: $nodes[0]->name->key,
            expectedOperand: '0',
            expectedType: Type::INT,
        );
    }

    public function testParsesArrayAccessThenPropertyAssignment(): void
    {
        $nodes = $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new AssignToken(
                        line: 1,
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'users',
                    ),
                    new CharacterToken(
                        line: 1,
                        op1: CharacterSymbol::LEFT_SQUARE_BRACKET->symbol(),
                    ),
                    new LiteralToken(
                        line: 1,
                        op1: '0',
                        op2: Type::INT->name,
                    ),
                    new CharacterToken(
                        line: 1,
                        op1: CharacterSymbol::RIGHT_SQUARE_BRACKET->symbol(),
                    ),
                    new CharacterToken(
                        line: 1,
                        op1: CharacterSymbol::DOT->symbol(),
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'name',
                    ),
                    new OperatorToken(
                        line: 1,
                        op1: AssignmentSymbol::ASSIGN->symbol(),
                    ),
                    new LiteralToken(
                        line: 1,
                        op1: 'foo',
                        op2: Type::STRING->name,
                    ),
                    new EndToken(
                        line: 1,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        self::assertInstanceOf(AssignmentNode::class, $nodes[0]);

        $this->assertPropertyAccessNode(
            node: $nodes[0]->name,
            expectedProperty: 'name',
        );

        self::assertInstanceOf(PropertyAccessNode::class, $nodes[0]->name);
        self::assertInstanceOf(ArrayAccessNode::class, $nodes[0]->name->accessor);
    }

    public function testParsesPropertyThenArrayAccessAssignment(): void
    {
        $nodes = $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new AssignToken(
                        line: 1,
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'user',
                    ),
                    new CharacterToken(
                        line: 1,
                        op1: CharacterSymbol::DOT->symbol(),
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'tags',
                    ),
                    new CharacterToken(
                        line: 1,
                        op1: CharacterSymbol::LEFT_SQUARE_BRACKET->symbol(),
                    ),
                    new LiteralToken(
                        line: 1,
                        op1: '0',
                        op2: Type::INT->name,
                    ),
                    new CharacterToken(
                        line: 1,
                        op1: CharacterSymbol::RIGHT_SQUARE_BRACKET->symbol(),
                    ),
                    new OperatorToken(
                        line: 1,
                        op1: AssignmentSymbol::ASSIGN->symbol(),
                    ),
                    new LiteralToken(
                        line: 1,
                        op1: 'foo',
                        op2: Type::STRING->name,
                    ),
                    new EndToken(
                        line: 1,
                    ),
                ],
            ),
        );

        self::assertCount(1, $nodes);

        self::assertInstanceOf(AssignmentNode::class, $nodes[0]);
        self::assertInstanceOf(ArrayAccessNode::class, $nodes[0]->name);
        self::assertInstanceOf(PropertyAccessNode::class, $nodes[0]->name->array);
    }

    public function testParsesArrayKeyAsIdentifier(): void
    {
        $nodes = $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new AssignToken(
                        line: 1,
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'users',
                    ),
                    new CharacterToken(
                        line: 1,
                        op1: CharacterSymbol::LEFT_SQUARE_BRACKET->symbol(),
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'index',
                    ),
                    new CharacterToken(
                        line: 1,
                        op1: CharacterSymbol::RIGHT_SQUARE_BRACKET->symbol(),
                    ),
                    new OperatorToken(
                        line: 1,
                        op1: AssignmentSymbol::ASSIGN->symbol(),
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

        self::assertInstanceOf(AssignmentNode::class, $nodes[0]);
        self::assertInstanceOf(ArrayAccessNode::class, $nodes[0]->name);

        self::assertNotNull($nodes[0]->name->key);

        $this->assertIdentifierNode(
            node: $nodes[0]->name->key,
            expectedName: 'index',
        );
    }

    public function testThrowsOnNonAssignmentOperator(): void
    {
        self::expectException(ParserException::class);

        $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new AssignToken(
                        line: 1,
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'x',
                    ),
                    new OperatorToken(
                        line: 1,
                        op1: BinarySymbol::ADD->symbol(),
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
    }

    public function testThrowsOnUnknownLiteralTypeInArrayKey(): void
    {
        self::expectException(ParserException::class);

        $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new AssignToken(
                        line: 1,
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'users',
                    ),
                    new CharacterToken(
                        line: 1,
                        op1: CharacterSymbol::LEFT_SQUARE_BRACKET->symbol(),
                    ),
                    new LiteralToken(
                        line: 1,
                        op1: 'x',
                        op2: 'GIBBERISH',
                    ),
                    new CharacterToken(
                        line: 1,
                        op1: CharacterSymbol::RIGHT_SQUARE_BRACKET->symbol(),
                    ),
                    new OperatorToken(
                        line: 1,
                        op1: AssignmentSymbol::ASSIGN->symbol(),
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
    }

    public function testThrowsWhenArrayKeyIsNotIdentifierOrLiteral(): void
    {
        self::expectException(ParserException::class);

        $this->handler->parse(
            parser: $this->parser,
            stream: new TokenStream(
                tokens: [
                    new AssignToken(
                        line: 1,
                    ),
                    new IdentifierToken(
                        line: 1,
                        op1: 'users',
                    ),
                    new CharacterToken(
                        line: 1,
                        op1: CharacterSymbol::LEFT_SQUARE_BRACKET->symbol(),
                    ),
                    new OperatorToken(
                        line: 1,
                        op1: BinarySymbol::ADD->symbol(),
                    ),
                    new CharacterToken(
                        line: 1,
                        op1: CharacterSymbol::RIGHT_SQUARE_BRACKET->symbol(),
                    ),
                    new OperatorToken(
                        line: 1,
                        op1: AssignmentSymbol::ASSIGN->symbol(),
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
    }
}
