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
use Tuxxedo\View\Lumi\Syntax\NativeType;
use Tuxxedo\View\Lumi\Syntax\Node\ArrayAccessNode;
use Tuxxedo\View\Lumi\Syntax\Node\BinaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Node\ExpressionNodeInterface;
use Tuxxedo\View\Lumi\Syntax\Node\FunctionCallNode;
use Tuxxedo\View\Lumi\Syntax\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Syntax\Node\LiteralNode;
use Tuxxedo\View\Lumi\Syntax\Node\MethodCallNode;
use Tuxxedo\View\Lumi\Syntax\Node\PropertyAccessNode;
use Tuxxedo\View\Lumi\Syntax\Node\UnaryOpNode;
use Tuxxedo\View\Lumi\Syntax\Operator\BinarySymbol;
use Tuxxedo\View\Lumi\Syntax\Operator\CharacterSymbol;
use Tuxxedo\View\Lumi\Syntax\Operator\Precedence;
use Tuxxedo\View\Lumi\Syntax\Operator\UnarySymbol;
use Tuxxedo\View\Lumi\Syntax\Token\BuiltinTokenNames;
use Tuxxedo\View\Lumi\Syntax\Token\TokenInterface;

class ExpressionParser implements ExpressionParserInterface
{
    public private(set) TokenStreamInterface $stream;
    public private(set) TokenInterface $current;

    /**
     * @var array<string, Precedence>
     */
    public readonly array $precedences;

    public function __construct()
    {
        $precedences = [];

        foreach ([...BinarySymbol::cases(), ...CharacterSymbol::cases(), ...UnarySymbol::cases()] as $operator) {
            $precedences[$operator->symbol()] = $operator->precedence();
        }

        $this->precedences = $precedences;
    }

    public function parse(
        TokenStreamInterface $stream,
        int $startingLine,
    ): ExpressionNodeInterface {
        if ($stream->eof()) {
            throw ParserException::fromEmptyExpression(
                line: $startingLine,
            );
        }

        $this->stream = $stream;
        $node = $this->parseExpression(Precedence::LOWEST);

        unset($this->stream, $this->current);

        if (!$stream->eof()) {
            $this->unexpected($stream->current());
        }

        return $node;
    }

    /**
     * @throws ParserException
     */
    private function advance(): void
    {
        if ($this->stream->eof()) {
            throw ParserException::fromTokenStreamEof();
        }

        $this->current = $this->stream->current();

        $this->stream->consume();
    }

    /**
     * @throws ParserException
     */
    private function parseExpression(
        Precedence $rbp,
    ): ExpressionNodeInterface {
        $this->advance();

        $left = $this->nud($this->current);

        while ($rbp->value < $this->lbp($this->current)->value) {
            $t = $this->current;

            $this->advance();

            $left = $this->led($t, $left);
        }

        return $left;
    }

    /**
     * @throws ParserException
     */
    private function nud(
        TokenInterface $token,
    ): ExpressionNodeInterface {
        return match (true) {
            $token->type === BuiltinTokenNames::LITERAL->name => $this->literalNode($token),
            $token->type === BuiltinTokenNames::IDENTIFIER->name => $this->identifierNode($token),
            $token->type === BuiltinTokenNames::CHARACTER->name && UnarySymbol::is($token) => $this->unaryNode($token),
            $token->type === BuiltinTokenNames::CHARACTER->name && $token->op1 === CharacterSymbol::LEFT_PARENTHESIS->symbol() => $this->parseGroupedExpression(),
            default => $this->unexpected($token),
        };
    }

    /**
     * @throws ParserException
     */
    private function lbp(
        TokenInterface $token,
    ): Precedence {
        if ($token->op1 === null) {
            throw ParserException::fromMalformedToken();
        }

        if (
            $token->type === BuiltinTokenNames::OPERATOR->name &&
            \array_key_exists($token->op1, $this->precedences)
        ) {
            return $this->precedences[$token->op1];
        }

        return Precedence::LOWEST;
    }

    private function led(
        TokenInterface $token,
        ExpressionNodeInterface $left,
    ): ExpressionNodeInterface {
        if ($token->op1 === null) {
            throw ParserException::fromMalformedToken();
        }

        if (
            $token->type === BuiltinTokenNames::OPERATOR->name &&
            BinarySymbol::is($token)
        ) {
            $operator = BinarySymbol::from($token);

            return new BinaryOpNode(
                operator: $operator,
                left: $left,
                right: $this->parseExpression($operator->precedence()),
            );
        }

        if (
            $token->type === BuiltinTokenNames::CHARACTER->name &&
            $token->op1 === CharacterSymbol::LEFT_PARENTHESIS->symbol()
        ) {
            $args = [];

            if (
                !(
                    $this->current->type === BuiltinTokenNames::CHARACTER->name &&
                    $this->current->op1 === CharacterSymbol::RIGHT_PARENTHESIS->symbol()
                )
            ) {
                while (
                    $this->current->type === BuiltinTokenNames::CHARACTER->name &&
                    $this->current->op1 === CharacterSymbol::COMMA->symbol()
                ) {
                    $this->advance();

                    $args[] = $this->parseExpression(Precedence::LOWEST);
                }
            }

            if (
                !(
                    $this->current->type === BuiltinTokenNames::CHARACTER->name &&
                    $this->current->op1 === CharacterSymbol::RIGHT_PARENTHESIS->symbol()
                )
            ) {
                $this->unexpected($this->current);
            }

            $this->advance();

            return new FunctionCallNode(
                name: $left,
                arguments: $args,
            );
        }

        if (
            $token->type === BuiltinTokenNames::CHARACTER->name &&
            $token->op1 === CharacterSymbol::DOT->symbol()
        ) {
            if (
                $this->current->type !== BuiltinTokenNames::IDENTIFIER->name ||
                $this->current->op1 === null
            ) {
                $this->unexpected($this->current);
            }

            $name = $this->current->op1;
            $this->advance();

            $node = new PropertyAccessNode(
                accessor: $left,
                property: $name,
            );

            if (
                $this->current->type === BuiltinTokenNames::CHARACTER->name &&
                $this->current->op1 === CharacterSymbol::LEFT_PARENTHESIS->symbol()
            ) {
                $this->advance();
                $args = [];

                if (
                    !(
                        $this->current->type === BuiltinTokenNames::CHARACTER->name &&
                        $this->current->op1 === CharacterSymbol::RIGHT_PARENTHESIS->symbol()
                    )
                ) {
                    while (
                        $this->current->type === BuiltinTokenNames::CHARACTER->name &&
                        $this->current->op1 === CharacterSymbol::COMMA->symbol()
                    ) {
                        $this->advance();

                        $args[] = $this->parseExpression(Precedence::LOWEST);
                    }
                }

                if (
                    !(
                        $this->current->type === BuiltinTokenNames::CHARACTER->name &&
                        $this->current->op1 === CharacterSymbol::RIGHT_PARENTHESIS->symbol()
                    )
                ) {
                    $this->unexpected($this->current);
                }

                $this->advance();

                return new MethodCallNode(
                    caller: $left,
                    name: $name,
                    arguments: $args,
                );
            }

            return $node;
        }

        if (
            $token->type === BuiltinTokenNames::CHARACTER->name &&
            $token->op1 === CharacterSymbol::LEFT_SQUARE_BRACKET->symbol()
        ) {
            $index = $this->parseExpression(Precedence::LOWEST);

            if (
                !(
                    $this->current->type === BuiltinTokenNames::CHARACTER->name &&
                    $this->current->op1 === CharacterSymbol::RIGHT_SQUARE_BRACKET->symbol()
                )
            ) {
                $this->unexpected($this->current);
            }

            $this->advance();

            return new ArrayAccessNode(
                array: $left,
                key: $index,
            );
        }

        $this->unexpected($token);
    }

    private function parseGroupedExpression(): ExpressionNodeInterface
    {
        $expr = $this->parseExpression(Precedence::LOWEST);

        if (
            $this->current->type !== BuiltinTokenNames::CHARACTER->name ||
            $this->current->op1 !== CharacterSymbol::RIGHT_PARENTHESIS->symbol()
        ) {
            $this->unexpected($this->current);
        }

        $this->advance();

        return $expr;
    }

    /**
     * @throws ParserException
     */
    private function literalNode(
        TokenInterface $token,
    ): LiteralNode {
        if ($token->op1 === null || $token->op2 === null) {
            throw ParserException::fromMalformedToken();
        }

        return new LiteralNode(
            operand: $token->op1,
            type: NativeType::fromString(
                name: $token->op2,
                line: $token->line,
            ),
        );
    }

    /**
     * @throws ParserException
     */
    private function identifierNode(
        TokenInterface $token,
    ): IdentifierNode {
        if ($token->op1 === null) {
            throw ParserException::fromMalformedToken();
        }

        return new IdentifierNode(
            name: $token->op1,
        );
    }

    /**
     * @throws ParserException
     */
    private function unaryNode(
        TokenInterface $token,
    ): UnaryOpNode {
        $operator = UnarySymbol::from($token);

        return new UnaryOpNode(
            operator: $operator,
            operand: $this->parseExpression($operator->precedence()),
        );
    }

    /**
     * @throws ParserException
     */
    private function unexpected(
        TokenInterface $token,
    ): never {
        if (
            (
                $token->type === BuiltinTokenNames::OPERATOR->name ||
                $token->type === BuiltinTokenNames::CHARACTER->name
            ) &&
            $token->op1 !== null
        ) {
            $tokenName = $token->op1;
        }

        throw ParserException::fromUnexpectedToken(
            tokenName: $tokenName ?? $token->type,
            line: $token->line,
        );
    }
}
