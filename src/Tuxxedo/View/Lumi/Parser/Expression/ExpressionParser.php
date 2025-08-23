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
use Tuxxedo\View\Lumi\Node\ExpressionNodeInterface;
use Tuxxedo\View\Lumi\Node\FunctionCallNode;
use Tuxxedo\View\Lumi\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Node\LiteralNode;
use Tuxxedo\View\Lumi\Node\MethodCallNode;
use Tuxxedo\View\Lumi\Node\NodeNativeType;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Parser\ParserStateInterface;
use Tuxxedo\View\Lumi\Syntax\BinaryOperator;
use Tuxxedo\View\Lumi\Syntax\CharacterSymbol;
use Tuxxedo\View\Lumi\Token\BuiltinTokenNames;
use Tuxxedo\View\Lumi\Token\TokenInterface;

class ExpressionParser implements ExpressionParserInterface
{
    public function parse(
        TokenStreamInterface $stream,
        ParserStateInterface $state,
    ): ExpressionNodeInterface {
        $node = $this->parsePrimary(
            stream: $stream,
            state: $state,
        );

        if (!$state->isAllGroupingsClosed()) {
            throw ParserException::fromUnexpectedGroupingExit();
        }

        return $node;
    }

    private function parsePrimary(
        TokenStreamInterface $stream,
        ParserStateInterface $state,
    ): ExpressionNodeInterface {
        $token = $stream->current();

        $stream->consume();

        return match ($token->type) {
            BuiltinTokenNames::CHARACTER->name => $this->startParseCharacter($token, $stream, $state),
            BuiltinTokenNames::OPERATOR->name => $this->startParseOperator($token, $stream, $state),
            BuiltinTokenNames::VARIABLE->name => $this->startParseVariable($token, $stream, $state),
            BuiltinTokenNames::TYPE->name => $this->startParseType($token, $stream, $state),
            default => throw ParserException::fromUnexpectedTokenWithExpectsOneOf(
                tokenName: $token->type,
                expectedTokenNames: [
                    BuiltinTokenNames::CHARACTER->name,
                    BuiltinTokenNames::OPERATOR->name,
                    BuiltinTokenNames::VARIABLE->name,
                    BuiltinTokenNames::TYPE->name,
                ],
            ),
        };
    }

    /**
     * @throws ParserException
     */
    private function getBinaryOp(
        string $operator,
    ): BinaryOperator {
        $binaryOperator = null;
        $allBinaryOperators = [
            ...BinaryOperator::cases(),
        ];

        foreach ($allBinaryOperators as $potentialOperator) {
            if ($potentialOperator->symbol() === $operator) {
                $binaryOperator = $potentialOperator;

                break;
            }
        }

        if ($binaryOperator === null) {
            throw ParserException::fromUnexpectedTokenWithExpectsOneOf(
                tokenName: $operator,
                expectedTokenNames: \array_map(
                    static fn (BinaryOperator $binaryOperator): string => $binaryOperator->symbol(),
                    $allBinaryOperators,
                ),
            );
        }

        return $binaryOperator;
    }

    /**
     * @throws ParserException
     */
    private function startParseCharacter(
        TokenInterface $token,
        TokenStreamInterface $stream,
        ParserStateInterface $state,
    ): ExpressionNodeInterface {
        if ($token->op1 === null) {
            throw ParserException::fromMalformedToken();
        }

        // ...

        throw ParserException::fromNotImplemented(
            feature: 'Character parsing',
        );
    }

    /**
     * @throws ParserException
     */
    private function startParseOperator(
        TokenInterface $token,
        TokenStreamInterface $stream,
        ParserStateInterface $state,
    ): ExpressionNodeInterface {
        if ($token->op1 === null) {
            throw ParserException::fromMalformedToken();
        }

        // ...

        throw ParserException::fromNotImplemented(
            feature: 'Operator parsing',
        );
    }

    /**
     * @throws ParserException
     */
    private function startParseVariable(
        TokenInterface $token,
        TokenStreamInterface $stream,
        ParserStateInterface $state,
    ): ExpressionNodeInterface {
        if ($token->op1 === null) {
            throw ParserException::fromMalformedToken();
        }

        if ($stream->eof()) {
            return new IdentifierNode(
                name: $token->op1,
            );
        }

        $node = null;
        $nextToken = $stream->current();

        if ($nextToken->type === BuiltinTokenNames::CHARACTER->name) {
            if ($nextToken->op1 === null) {
                throw ParserException::fromMalformedToken();
            }

            if ($nextToken->op1 === CharacterSymbol::DOT->symbol()) {
                $node = $this->parseMethodCallByToken(
                    token: $token,
                    stream: $stream,
                    state: $state,
                );
            }

            if ($nextToken->op1 === CharacterSymbol::LEFT_PARENTHESIS->symbol()) {
                $node = $this->parseFunctionCallByToken(
                    token: $token,
                    stream: $stream,
                    state: $state,
                );
            }

            if ($nextToken->op1 === CharacterSymbol::LEFT_SQUARE_BRACKET->symbol()) {
                $node = $this->parseArrayAccessByToken(
                    token: $token,
                    stream: $stream,
                    state: $state,
                );
            }

            if ($node === null) {
                throw ParserException::fromUnexpectedTokenWithExpectsOneOf(
                    tokenName: $nextToken->type,
                    expectedTokenNames: [
                        CharacterSymbol::DOT->name,
                        CharacterSymbol::LEFT_PARENTHESIS->name,
                        CharacterSymbol::LEFT_SQUARE_BRACKET->name,
                    ],
                );
            }
        }

        if ($nextToken->type === BuiltinTokenNames::OPERATOR->name) {
            return $this->parseBinaryOp(
                stream: $stream,
                state: $state,
                left: $node ?? new IdentifierNode(
                    name: $token->op1,
                ),
            );
        } elseif ($node !== null) {
            return $node;
        }

        throw ParserException::fromUnexpectedTokenWithExpectsOneOf(
            tokenName: $nextToken->type,
            expectedTokenNames: [
                CharacterSymbol::DOT->name,
                CharacterSymbol::LEFT_PARENTHESIS->name,
                CharacterSymbol::LEFT_SQUARE_BRACKET->name,
                BuiltinTokenNames::OPERATOR->name,
            ],
        );
    }

    private function parseMethodCallByToken(
        TokenInterface $token,
        TokenStreamInterface $stream,
        ParserStateInterface $state,
    ): MethodCallNode {
        if ($token->op1 === null) {
            throw ParserException::fromMalformedToken();
        }

        $stream->expect(BuiltinTokenNames::CHARACTER->name, CharacterSymbol::DOT->symbol());
        $methodToken = $stream->expect(BuiltinTokenNames::VARIABLE->name);

        if ($methodToken->op1 === null) {
            throw ParserException::fromMalformedToken();
        }

        $stream->expect(BuiltinTokenNames::CHARACTER->name, CharacterSymbol::LEFT_PARENTHESIS->symbol());

        $arguments = [];
        $nextToken = $stream->peek();

        if (
            $nextToken !== null &&
            $nextToken->type !== BuiltinTokenNames::CHARACTER->name &&
            $nextToken->op1 !== CharacterSymbol::RIGHT_PARENTHESIS->symbol()
        ) {
            throw ParserException::fromNotImplemented(
                feature: 'Method call arguments',
            );
        }

        $stream->expect(BuiltinTokenNames::CHARACTER->name, CharacterSymbol::RIGHT_PARENTHESIS->symbol());

        // @todo Caller may be more expressive, keep that in mind
        return new MethodCallNode(
            caller: new IdentifierNode(
                name: $token->op1,
            ),
            name: $methodToken->op1,
            arguments: $arguments,
        );
    }

    private function parseFunctionCallByToken(
        TokenInterface $token,
        TokenStreamInterface $stream,
        ParserStateInterface $state,
    ): FunctionCallNode {
        if ($token->op1 === null) {
            throw ParserException::fromMalformedToken();
        }

        $stream->expect(BuiltinTokenNames::CHARACTER->name, CharacterSymbol::LEFT_PARENTHESIS->symbol());

        $arguments = [];
        $nextToken = $stream->peek();

        if (
            $nextToken !== null &&
            $nextToken->type !== BuiltinTokenNames::CHARACTER->name &&
            $nextToken->op1 !== CharacterSymbol::RIGHT_PARENTHESIS->symbol()
        ) {
            throw ParserException::fromNotImplemented(
                feature: 'Function call arguments',
            );
        }

        $stream->expect(BuiltinTokenNames::CHARACTER->name, CharacterSymbol::RIGHT_PARENTHESIS->symbol());

        return new FunctionCallNode(
            name: $token->op1,
            arguments: $arguments,
        );
    }

    private function parseArrayAccessByToken(
        TokenInterface $token,
        TokenStreamInterface $stream,
        ParserStateInterface $state,
    ): ExpressionNodeInterface {
        // @todo Implement

        throw ParserException::fromNotImplemented(
            feature: 'Array access',
        );
    }

    /**
     * @throws ParserException
     */
    private function startParseType(
        TokenInterface $token,
        TokenStreamInterface $stream,
        ParserStateInterface $state,
    ): ExpressionNodeInterface {
        if ($token->op1 === null || $token->op2 === null) {
            throw ParserException::fromMalformedToken();
        }

        $node = new LiteralNode(
            operand: $token->op1,
            type: NodeNativeType::fromTokenNativeType($token->op2),
        );

        if ($stream->eof()) {
            return $node;
        }

        $nextTokenType = $stream->peek()?->type;

        if ($nextTokenType === BuiltinTokenNames::OPERATOR->name) {
            return $this->parseBinaryOp(
                stream: $stream,
                state: $state,
                left: $node,
            );
        } elseif ($nextTokenType === null) {
            throw ParserException::fromTokenStreamEof();
        }

        throw ParserException::fromUnexpectedTokenWithExpects(
            tokenName: $nextTokenType,
            expectedTokenName: BuiltinTokenNames::OPERATOR->name,
        );
    }

    /**
     * @throws ParserException
     */
    private function parseBinaryOp(
        TokenStreamInterface $stream,
        ParserStateInterface $state,
        ?ExpressionNodeInterface $left = null,
    ): ExpressionNodeInterface {
        $left ??= $this->parsePrimary(
            stream: $stream,
            state: $state,
        );

        // @todo Remove
        $this->getBinaryOp('=');

        // ...

        throw ParserException::fromNotImplemented(
            feature: 'Binary expressions',
        );
    }
}
