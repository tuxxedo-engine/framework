<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\View\Lumi\Parser\Expression;

use Tuxxedo\View\Lumi\Lexer\TokenStreamInterface;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Syntax\Node\ArrayAccessNode;
use Tuxxedo\View\Lumi\Syntax\Node\ArrayItemNode;
use Tuxxedo\View\Lumi\Syntax\Node\ArrayNode;
use Tuxxedo\View\Lumi\Syntax\Node\BinaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Node\ExpressionNodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\FilterOrBitwiseOrNode;
use Tuxxedo\View\Lumi\Syntax\Node\FunctionCallNode;
use Tuxxedo\View\Lumi\Syntax\Node\GroupNode;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Node\MethodCallNode;
use Tuxxedo\View\Lumi\Syntax\Node\PropertyAccessNode;
use Tuxxedo\View\Lumi\Syntax\Node\UnaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Operator\Associativity;
use Tuxxedo\View\Lumi\Syntax\Operator\BinarySymbol;
use Tuxxedo\View\Lumi\Syntax\Operator\CharacterSymbol;
use Tuxxedo\View\Lumi\Syntax\Operator\Precedence;
use Tuxxedo\View\Lumi\Syntax\Operator\UnarySymbol;
use Tuxxedo\View\Lumi\Syntax\Token\CharacterToken;
use Tuxxedo\View\Lumi\Syntax\Token\IdentifierToken;
use Tuxxedo\View\Lumi\Syntax\Token\LiteralToken;
use Tuxxedo\View\Lumi\Syntax\Token\OperatorToken;
use Tuxxedo\View\Lumi\Syntax\Token\TokenInterface;
use Tuxxedo\View\Lumi\Syntax\Type;

class ExpressionParser implements ExpressionParserInterface
{
    public function parse(
        TokenStreamInterface $stream,
        int $startingLine,
    ): ExpressionNodeInterface {
        if ($stream->eof()) {
            throw ParserException::fromEmptyExpression(
                line: $startingLine,
            );
        }

        $node = $this->parseExpression($stream, Precedence::LOWEST);

        if (!$stream->eof()) {
            $token = $stream->current();

            throw ParserException::fromUnexpectedToken(
                tokenName: $token::name(),
                line: $token->line,
            );
        }

        return $node;
    }

    private function parseExpression(
        TokenStreamInterface $stream,
        Precedence|int $rightBindingPower,
    ): ExpressionNodeInterface {
        $left = $this->nud($stream, $stream->current());

        if ($rightBindingPower instanceof Precedence) {
            $rightBindingPower = $rightBindingPower->value;
        }

        while (!$stream->eof()) {
            $token = $stream->current();

            if ($rightBindingPower >= $this->lbp($token)->value) {
                break;
            }

            $left = $this->led($stream, $token, $left);
        }

        return $left;
    }

    /**
     * @throws ParserException
     */
    private function nud(
        TokenStreamInterface $stream,
        TokenInterface $token,
    ): ExpressionNodeInterface {
        switch (true) {
            case $token instanceof LiteralToken:
                $stream->consume();

                $type = Type::fromString($token->op2);

                if ($type === null) {
                    throw ParserException::fromUnexpectedToken(
                        tokenName: $token->op2,
                        line: $token->line,
                    );
                }

                return new LiteralNode(
                    operand: $token->op1,
                    type: $type,
                );

            case $token instanceof IdentifierToken:
                $stream->consume();

                return new IdentifierNode(
                    name: $token->op1,
                );

            case $token instanceof CharacterToken:
                if ($token->op1 === CharacterSymbol::LEFT_PARENTHESIS->symbol()) {
                    $stream->consume();

                    $operand = $this->parseExpression($stream, Precedence::LOWEST);

                    $this->expectCharacter(
                        stream: $stream,
                        character: CharacterSymbol::RIGHT_PARENTHESIS,
                    );

                    return new GroupNode(
                        operand: $operand,
                    );
                }

                if ($token->op1 === CharacterSymbol::LEFT_SQUARE_BRACKET->symbol()) {
                    $stream->consume();

                    $items = $this->parseArrayItems($stream);

                    $this->expectCharacter(
                        stream: $stream,
                        character: CharacterSymbol::RIGHT_SQUARE_BRACKET,
                    );

                    return new ArrayNode(
                        items: $items,
                    );
                }

                throw ParserException::fromUnexpectedToken(
                    tokenName: $token->op1,
                    line: $token->line,
                );

            case $token instanceof OperatorToken:
                if ($token->op1 === BinarySymbol::NULL_SAFE_ACCESS->symbol()) {
                    throw ParserException::fromUnexpectedToken(
                        tokenName: $token->op1,
                        line: $token->line,
                    );
                }

                if (
                    $token->op1 === UnarySymbol::NEGATE->symbol() ||
                    $token->op1 === UnarySymbol::NOT->symbol() ||
                    $token->op1 === UnarySymbol::BITWISE_NOT->symbol() ||
                    $token->op1 === UnarySymbol::INCREMENT_PRE->symbol() ||
                    $token->op1 === UnarySymbol::DECREMENT_PRE->symbol()
                ) {
                    $stream->consume();

                    $operator = UnarySymbol::from($token);

                    return new UnaryOpNode(
                        operand: $this->parseExpression($stream, $operator->precedence()),
                        operator: $operator,
                    );
                }
        }

        throw ParserException::fromUnexpectedToken(
            tokenName: $token::name(),
            line: $token->line,
        );
    }

    private function led(
        TokenStreamInterface $stream,
        TokenInterface $token,
        ExpressionNodeInterface $left,
    ): ExpressionNodeInterface {
        if ($token instanceof CharacterToken) {
            if ($token->op1 === CharacterSymbol::LEFT_PARENTHESIS->symbol()) {
                $stream->consume();

                $arguments = $this->parseArgumentList($stream);

                $this->expectCharacter(
                    stream: $stream,
                    character: CharacterSymbol::RIGHT_PARENTHESIS,
                );

                return new FunctionCallNode(
                    name: $left,
                    arguments: $arguments,
                );
            }

            if ($token->op1 === CharacterSymbol::LEFT_SQUARE_BRACKET->symbol()) {
                $stream->consume();

                $key = $this->parseExpression($stream, Precedence::LOWEST);

                $this->expectCharacter(
                    stream: $stream,
                    character: CharacterSymbol::RIGHT_SQUARE_BRACKET,
                );

                return new ArrayAccessNode(
                    array: $left,
                    key: $key,
                );
            }

            if ($token->op1 === CharacterSymbol::DOT->symbol()) {
                $stream->consume();

                $name = $stream->current();

                if (
                    !$name instanceof IdentifierToken
                ) {
                    throw ParserException::fromUnexpectedToken(
                        tokenName: $name::name(),
                        line: $name->line,
                    );
                }

                $stream->consume();

                if (
                    !$stream->eof() &&
                    $stream->currentIs(CharacterToken::class, CharacterSymbol::LEFT_PARENTHESIS->symbol())
                ) {
                    $stream->consume();

                    $arguments = $this->parseArgumentList($stream);

                    $this->expectCharacter(
                        stream: $stream,
                        character: CharacterSymbol::RIGHT_PARENTHESIS,
                    );

                    return new MethodCallNode(
                        caller: $left,
                        name: $name->op1,
                        arguments: $arguments,
                    );
                }

                return new PropertyAccessNode(
                    accessor: $left,
                    property: $name->op1,
                );
            }
        }

        if ($token instanceof OperatorToken) {
            if ($token->op1 === BinarySymbol::NULL_SAFE_ACCESS->symbol()) {
                $stream->consume();

                $after = $stream->current();

                if ($after instanceof IdentifierToken) {
                    $stream->consume();

                    $maybeCall = $stream->peek();

                    if (
                        $maybeCall !== null &&
                        $maybeCall instanceof CharacterToken &&
                        $maybeCall->op1 === CharacterSymbol::LEFT_PARENTHESIS->symbol()
                    ) {
                        $stream->consume();

                        $arguments = $this->parseArgumentList($stream);

                        $this->expectCharacter(
                            stream: $stream,
                            character: CharacterSymbol::RIGHT_PARENTHESIS,
                        );

                        return new MethodCallNode(
                            caller: $left,
                            name: $after->op1,
                            arguments: $arguments,
                            nullSafe: true,
                        );
                    }

                    return new PropertyAccessNode(
                        accessor: $left,
                        property: $after->op1,
                        nullSafe: true,
                    );
                }

                throw ParserException::fromUnexpectedToken(
                    tokenName: $after->op1 ?? $after::name(),
                    line: $after->line,
                );
            }

            if (UnarySymbol::is($token, post: true)) {
                $operator = UnarySymbol::from($token, post: true);

                if (
                    $operator === UnarySymbol::INCREMENT_POST ||
                    $operator === UnarySymbol::DECREMENT_POST
                ) {
                    $stream->consume();

                    return new UnaryOpNode(
                        operand: $left,
                        operator: $operator,
                    );
                }
            }

            if (BinarySymbol::is($token)) {
                $operator = BinarySymbol::from($token);

                $stream->consume();

                $next = $operator->associativity() === Associativity::RIGHT
                    ? $operator->precedence()->value - 1
                    : $operator->precedence();

                $right = $this->parseExpression(
                    stream: $stream,
                    rightBindingPower: $next,
                );

                if ($operator === BinarySymbol::BITWISE_OR) {
                    if ($right instanceof LiteralNode) {
                        return new BinaryOpNode(
                            left: $left,
                            right: $right,
                            operator: $operator,
                        );
                    }

                    return new FilterOrBitwiseOrNode(
                        left: $left,
                        right: $right,
                    );
                }

                return new BinaryOpNode(
                    left: $left,
                    right: $right,
                    operator: $operator,
                );
            }
        }

        throw ParserException::fromUnexpectedToken(
            tokenName: $token->op1 ?? $token::name(),
            line: $token->line,
        );
    }

    private function lbp(
        TokenInterface $token,
    ): Precedence {
        if ($token instanceof CharacterToken) {
            return CharacterSymbol::from($token)->precedence();
        }

        if ($token instanceof OperatorToken) {
            if (
                $token->op1 === UnarySymbol::INCREMENT_POST->symbol() ||
                $token->op1 === UnarySymbol::DECREMENT_POST->symbol()
            ) {
                return UnarySymbol::from($token)->precedence();
            }

            if (BinarySymbol::is($token)) {
                return BinarySymbol::from($token)->precedence();
            }
        }

        return Precedence::LOWEST;
    }

    /**
     * @return ExpressionNodeInterface[]
     */
    private function parseArgumentList(
        TokenStreamInterface $stream,
    ): array {
        if ($stream->eof()) {
            return [];
        }

        $token = $stream->current();

        if (
            $token instanceof CharacterToken &&
            $token->op1 === CharacterSymbol::RIGHT_PARENTHESIS->symbol()
        ) {
            return [];
        }

        $arguments = [
            $this->parseExpression($stream, Precedence::LOWEST),
        ];

        while (!$stream->eof()) {
            $token = $stream->current();

            if (
                $token instanceof CharacterToken &&
                $token->op1 === CharacterSymbol::COMMA->symbol()
            ) {
                $stream->consume();

                $token = $stream->current();

                if (
                    $token instanceof CharacterToken &&
                    $token->op1 === CharacterSymbol::RIGHT_PARENTHESIS->symbol()
                ) {
                    break;
                }

                $arguments[] = $this->parseExpression($stream, Precedence::LOWEST);

                continue;
            }

            break;
        }

        return $arguments;
    }

    /**
     * @return ArrayItemNode[]
     */
    private function parseArrayItems(
        TokenStreamInterface $stream,
    ): array {
        $token = $stream->current();

        if (
            $token instanceof CharacterToken &&
            $token->op1 === CharacterSymbol::RIGHT_SQUARE_BRACKET->symbol()
        ) {
            return [];
        }

        $items = [
            $this->parseArrayItem($stream),
        ];

        while (!$stream->eof()) {
            $token = $stream->current();

            if (
                $token instanceof CharacterToken &&
                $token->op1 === CharacterSymbol::COMMA->symbol()
            ) {
                $stream->consume();

                $token = $stream->current();

                if (
                    $token instanceof CharacterToken &&
                    $token->op1 === CharacterSymbol::RIGHT_SQUARE_BRACKET->symbol()
                ) {
                    break;
                }

                $items[] = $this->parseArrayItem($stream);

                continue;
            }

            break;
        }

        return $items;
    }

    private function parseArrayItem(
        TokenStreamInterface $stream,
    ): ArrayItemNode {
        $expr = $this->parseExpression($stream, Precedence::LOWEST);
        $token = $stream->current();

        if (
            $token instanceof CharacterToken &&
            $token->op1 === CharacterSymbol::COLON->symbol()
        ) {
            $stream->consume();

            return new ArrayItemNode(
                value: $this->parseExpression($stream, Precedence::LOWEST),
                key: $expr,
            );
        }

        return new ArrayItemNode(
            value: $expr,
        );
    }

    /**
     * @throws ParserException
     */
    private function expectCharacter(
        TokenStreamInterface $stream,
        CharacterSymbol $character,
    ): void {
        $token = $stream->current();

        if (!$token instanceof CharacterToken) {
            throw ParserException::fromUnexpectedToken(
                tokenName: $token::name(),
                line: $token->line,
            );
        }

        if ($token->op1 !== $character->symbol()) {
            throw ParserException::fromUnexpectedToken(
                tokenName: $token->op1,
                line: $token->line,
            );
        }

        $stream->consume();
    }
}
