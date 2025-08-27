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

use Tuxxedo\View\Lumi\Lexer\TokenStream;
use Tuxxedo\View\Lumi\Node\ExpressionNodeInterface;
use Tuxxedo\View\Lumi\Node\FunctionCallNode;
use Tuxxedo\View\Lumi\Node\IdentifierNode;
use Tuxxedo\View\Lumi\Node\MethodCallNode;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Syntax\CharacterSymbol;
use Tuxxedo\View\Lumi\Token\BuiltinTokenNames;
use Tuxxedo\View\Lumi\Token\TokenInterface;

class InvocationParser implements InvocationParserInterface
{
    public function __construct(
        private readonly ExpressionParserInterface $parser,
    ) {
    }

    /**
     * @return ExpressionNodeInterface[]
     *
     * @throws ParserException
     */
    private function parseArguments(): array
    {
        $tokens = [];
        $depth = 0;
        $argumentNo = 0;

        while (!$this->parser->stream->eof()) {
            if ($this->parser->stream->currentIs(BuiltinTokenNames::CHARACTER->name)) {
                $character = $this->parser->stream->current();

                if ($character->op1 === null) {
                    throw ParserException::fromMalformedToken();
                }

                if ($character->op1 === CharacterSymbol::LEFT_PARENTHESIS->symbol()) {
                    $this->parser->stream->consume();
                    $depth++;

                    continue;
                } elseif ($character->op1 === CharacterSymbol::RIGHT_PARENTHESIS->symbol()) {
                    $this->parser->stream->consume();

                    if ($depth === 0) {
                        break;
                    }

                    $depth--;

                    continue;
                } elseif ($character->op1 === CharacterSymbol::COMMA->symbol()) {
                    $this->parser->stream->consume();

                    if ($depth === 0) {
                        break;
                    }

                    $argumentNo++;

                    continue;
                }
            }

            $tokens[$argumentNo] ??= [];
            $tokens[$argumentNo][] = $this->parser->stream->current();

            $this->parser->stream->consume();
        }

        if ($depth !== 0) {
            throw new \Exception('Write a better exception that explains unclosed )');
        }

        $nodes = [];

        foreach ($tokens as $index => $args) {
            $nodes[$index] = $this->parser->parse(
                stream: new TokenStream(
                    tokens: $args,
                ),
                state: $this->parser->state,
            );
        }

        return $nodes;
    }

    public function parseFunction(
        TokenInterface $caller,
    ): void {
        if ($this->parser->stream->currentIs(BuiltinTokenNames::CHARACTER->name, ')')) {
            $this->parser->stream->consume();

            if ($caller->type !== BuiltinTokenNames::IDENTIFIER->name) {
                throw ParserException::fromUnexpectedTokenWithExpects(
                    tokenName: $caller->type,
                    expectedTokenName: BuiltinTokenNames::IDENTIFIER->name,
                );
            } elseif ($caller->op1 === null) {
                throw ParserException::fromMalformedToken();
            }

            $this->parser->state->pushNode(
                node: new FunctionCallNode(
                    name: $caller->op1,
                    arguments: [],
                ),
            );

            return;
        } elseif ($caller->type !== BuiltinTokenNames::IDENTIFIER->name) {
            throw ParserException::fromUnexpectedTokenWithExpects(
                tokenName: $caller->type,
                expectedTokenName: BuiltinTokenNames::IDENTIFIER->name,
            );
        } elseif ($caller->op1 === null) {
            throw ParserException::fromMalformedToken();
        }

        $this->parser->state->pushNode(
            node: new FunctionCallNode(
                name: $caller->op1,
                arguments: $this->parseArguments(),
            ),
        );
    }

    public function parseMethodCall(
        TokenInterface $caller,
        TokenInterface $method,
    ): void {
        if ($this->parser->stream->currentIs(BuiltinTokenNames::CHARACTER->name, ')')) {
            $this->parser->stream->consume();

            if ($caller->type !== BuiltinTokenNames::IDENTIFIER->name) {
                throw ParserException::fromUnexpectedTokenWithExpects(
                    tokenName: $caller->type,
                    expectedTokenName: BuiltinTokenNames::IDENTIFIER->name,
                );
            } elseif ($method->type !== BuiltinTokenNames::IDENTIFIER->name) {
                throw ParserException::fromUnexpectedTokenWithExpects(
                    tokenName: $method->type,
                    expectedTokenName: BuiltinTokenNames::IDENTIFIER->name,
                );
            } elseif (
                $caller->op1 === null ||
                $method->op1 === null
            ) {
                throw ParserException::fromMalformedToken();
            }

            $this->parser->state->pushNode(
                node: new MethodCallNode(
                    caller: new IdentifierNode(
                        name: $caller->op1,
                    ),
                    name: $method->op1,
                    arguments: [],
                ),
            );

            return;
        } elseif ($caller->type !== BuiltinTokenNames::IDENTIFIER->name) {
            throw ParserException::fromUnexpectedTokenWithExpects(
                tokenName: $caller->type,
                expectedTokenName: BuiltinTokenNames::IDENTIFIER->name,
            );
        } elseif ($method->type !== BuiltinTokenNames::IDENTIFIER->name) {
            throw ParserException::fromUnexpectedTokenWithExpects(
                tokenName: $method->type,
                expectedTokenName: BuiltinTokenNames::IDENTIFIER->name,
            );
        } elseif (
            $caller->op1 === null ||
            $method->op1 === null
        ) {
            throw ParserException::fromMalformedToken();
        }

        $this->parser->state->pushNode(
            node: new MethodCallNode(
                caller: new IdentifierNode(
                    name: $caller->op1,
                ),
                name: $method->op1,
                arguments: $this->parseArguments(),
            ),
        );
    }
}
