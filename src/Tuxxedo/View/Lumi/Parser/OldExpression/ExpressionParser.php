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

namespace Tuxxedo\View\Lumi\Parser\OldExpression;

use Tuxxedo\View\Lumi\Lexer\TokenStreamInterface;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Parser\ParserStateInterface;
use Tuxxedo\View\Lumi\Syntax\Node\ExpressionNodeInterface;
use Tuxxedo\View\Lumi\Syntax\Operator\BinarySymbol;
use Tuxxedo\View\Lumi\Syntax\Operator\CharacterSymbol;
use Tuxxedo\View\Lumi\Syntax\Operator\UnarySymbol;
use Tuxxedo\View\Lumi\Syntax\Token\BuiltinTokenNames;

class ExpressionParser implements ExpressionParserInterface
{
    public readonly AtomicParserInterface $atomic;
    public readonly ArrayParserInterface $array;
    public readonly InvocationParserInterface $invocation;
    public readonly GroupingParserInterface $grouping;
    public readonly OperatorParserInterface $operator;

    public private(set) TokenStreamInterface $stream;
    public private(set) ParserStateInterface $state;

    public function __construct(
        ?AtomicParserInterface $atomic = null,
        ?ArrayParserInterface $array = null,
        ?InvocationParserInterface $invocation = null,
        ?GroupingParserInterface $grouping = null,
        ?OperatorParserInterface $operator = null,
    ) {
        $this->atomic = $atomic ?? new AtomicParser($this);
        $this->array = $array ?? new ArrayParser($this);
        $this->invocation = $invocation ?? new InvocationParser($this);
        $this->grouping = $grouping ?? new GroupingParser($this);
        $this->operator = $operator ?? new OperatorParser($this);
    }

    public function parse(
        TokenStreamInterface $stream,
        ParserStateInterface $state,
    ): ExpressionNodeInterface {
        if (isset($this->stream) && $this->stream !== $stream) {
            $oldStream = $this->stream;
        }

        $this->stream = $stream;
        $this->state = $state;

        $this->dispatch();

        if (\sizeof($this->state->nodeStack) > 1) {
            throw ParserException::fromNotImplemented(
                feature: 'Large expression node stack',
            );
        }

        $node = $this->state->popNode();

        if (!$this->state->isCleanState(checkLoops: false, checkConditions: false, checkCustom: false)) {
            throw ParserException::fromUnexpectedGroupingExit();
        }

        if (isset($oldStream)) {
            $this->stream = $oldStream;
        } else {
            unset($this->stream, $this->state);
        }

        return $node;
    }

    private function dispatch(): void
    {
        if ($this->stream->eof()) {
            throw ParserException::fromEmptyExpression();
        }

        do {
            $token = $this->stream->current();

            if ($token->type === BuiltinTokenNames::IDENTIFIER->name) {
                if ($token->op1 === null) {
                    throw ParserException::fromMalformedToken();
                }

                $this->stream->consume();

                if ($this->stream->eof()) {
                    $this->atomic->parseVariable(
                        variable: $token,
                    );

                    continue;
                }

                $next = $this->stream->current();

                if ($next->type === BuiltinTokenNames::CHARACTER->name) {
                    if ($next->op1 === null) {
                        throw ParserException::fromMalformedToken();
                    }

                    if ($next->op1 === CharacterSymbol::DOT->symbol()) {
                        $this->stream->consume();
                        $methodToken = $this->stream->current();

                        if ($methodToken->type !== BuiltinTokenNames::IDENTIFIER->name) {
                            throw ParserException::fromUnexpectedTokenWithExpects(
                                tokenName: $next->type,
                                expectedTokenName: BuiltinTokenNames::IDENTIFIER->name,
                            );
                        } elseif ($methodToken->op1 === null) {
                            throw ParserException::fromMalformedToken();
                        }

                        $this->stream->consume();
                        $next = $this->stream->current();

                        if ($next->type !== BuiltinTokenNames::CHARACTER->name) {
                            throw ParserException::fromUnexpectedToken(
                                tokenName: BuiltinTokenNames::CHARACTER->name,
                            );
                        } elseif ($next->op1 === null) {
                            throw ParserException::fromMalformedToken();
                        }

                        $this->stream->consume();

                        match ($next->op1) {
                            CharacterSymbol::LEFT_PARENTHESIS->symbol() => $this->invocation->parseMethodCall(
                                caller: $token,
                                method: $methodToken,
                            ),
                            CharacterSymbol::LEFT_SQUARE_BRACKET->symbol(), CharacterSymbol::DOT->symbol() => $this->grouping->parseDereferenceChain(
                                caller: $token,
                                method: $methodToken,
                            ),
                            default => throw ParserException::fromUnexpectedTokenWithExpectsOneOf(
                                tokenName: $next->op1,
                                expectedTokenNames: [
                                    CharacterSymbol::LEFT_PARENTHESIS->symbol(),
                                    CharacterSymbol::LEFT_SQUARE_BRACKET->symbol(),
                                    CharacterSymbol::DOT->symbol(),
                                ],
                            ),
                        };

                        continue;
                    }

                    if ($next->op1 === CharacterSymbol::LEFT_PARENTHESIS->symbol()) {
                        $this->stream->consume();
                        $this->invocation->parseFunction(
                            caller: $token,
                        );

                        continue;
                    } elseif ($next->op1 === CharacterSymbol::LEFT_SQUARE_BRACKET->symbol()) {
                        $this->stream->consume();
                        $this->array->parseAccess(
                            variable: $token,
                        );

                        continue;
                    }

                    throw ParserException::fromUnexpectedTokenWithExpectsOneOf(
                        tokenName: $next->op1,
                        expectedTokenNames: [
                            CharacterSymbol::LEFT_PARENTHESIS->symbol(),
                            CharacterSymbol::LEFT_SQUARE_BRACKET->symbol(),
                        ],
                    );
                }

                if ($next->type === BuiltinTokenNames::OPERATOR->name) {
                    if ($next->op1 === null) {
                        throw ParserException::fromMalformedToken();
                    }

                    $this->stream->consume();

                    if (UnarySymbol::is($next)) {
                        $this->operator->parseUnary(
                            operator: UnarySymbol::from($next),
                            operand: $token,
                        );

                        continue;
                    }

                    $this->operator->parseBinaryByToken(
                        left: $token,
                        operator: BinarySymbol::from($next),
                    );

                    continue;
                }

                $this->atomic->parseVariable(
                    variable: $token,
                );

                continue;
            }

            if ($token->type === BuiltinTokenNames::LITERAL->name) {
                if ($token->op1 === null || $token->op2 === null) {
                    throw ParserException::fromMalformedToken();
                }

                $this->stream->consume();
                $this->atomic->parseLiteral(
                    literal: $token,
                );

                continue;
            }

            if ($token->type === BuiltinTokenNames::OPERATOR->name) {
                if ($token->op1 === null) {
                    throw ParserException::fromMalformedToken();
                }

                if (!UnarySymbol::is($token)) {
                    if (BinarySymbol::is($token)) {
                        $this->operator->parseBinaryByNode(
                            left: $this->state->popNode(),
                            operator: BinarySymbol::from($token),
                        );
                    }

                    throw ParserException::fromUnexpectedTokenWithExpectsOneOf(
                        tokenName: $token->op1,
                        expectedTokenNames: UnarySymbol::all(),
                    );
                }

                $operand = $this->stream->peek();

                if ($operand === null) {
                    throw ParserException::fromTokenStreamEof();
                } elseif (
                    $operand->type !== BuiltinTokenNames::LITERAL->name &&
                    $operand->type !== BuiltinTokenNames::IDENTIFIER->name
                ) {
                    throw ParserException::fromUnexpectedTokenWithExpectsOneOf(
                        tokenName: $operand->type,
                        expectedTokenNames: [
                            BuiltinTokenNames::LITERAL->name,
                            BuiltinTokenNames::IDENTIFIER->name,
                        ],
                    );
                }

                $this->stream->consume();
                $this->operator->parseUnary(
                    operator: UnarySymbol::from($token),
                    operand: $operand,
                );

                continue;
            }

            if ($token->type === BuiltinTokenNames::CHARACTER->name) {
                if ($token->op1 === null) {
                    throw ParserException::fromMalformedToken();
                } elseif ($token->op1 !== CharacterSymbol::LEFT_PARENTHESIS->symbol()) {
                    throw ParserException::fromUnexpectedTokenWithExpects(
                        tokenName: $token->type,
                        expectedTokenName: CharacterSymbol::LEFT_PARENTHESIS->symbol(),
                    );
                }

                $this->stream->consume();
                $this->grouping->parseGroup();

                continue;
            }

            throw ParserException::fromUnexpectedTokenWithExpectsOneOf(
                tokenName: $token->type,
                expectedTokenNames: [
                    BuiltinTokenNames::IDENTIFIER->name,
                    BuiltinTokenNames::LITERAL->name,
                    BuiltinTokenNames::OPERATOR->name,
                    BuiltinTokenNames::CHARACTER->name,
                ],
            );
        } while (!$this->stream->eof());
    }
}
