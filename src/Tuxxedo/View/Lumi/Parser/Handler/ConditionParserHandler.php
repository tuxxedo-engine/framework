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

namespace Tuxxedo\View\Lumi\Parser\Handler;

use Tuxxedo\View\Lumi\Lexer\TokenStream;
use Tuxxedo\View\Lumi\Lexer\TokenStreamInterface;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Parser\ParserInterface;
use Tuxxedo\View\Lumi\Syntax\Node\ConditionalBranchNode;
use Tuxxedo\View\Lumi\Syntax\Node\ConditionalNode;
use Tuxxedo\View\Lumi\Syntax\Node\NodeInterface;
use Tuxxedo\View\Lumi\Syntax\Token\BuiltinTokenNames;

class ConditionParserHandler implements ParserHandlerInterface
{
    public private(set) string $tokenName = BuiltinTokenNames::IF->name;

    /**
     * @return NodeInterface[]
     */
    public function parse(
        ParserInterface $parser,
        TokenStreamInterface $stream,
    ): array {
        $stream->consume();

        if ($stream->currentIs(BuiltinTokenNames::END->name)) {
            throw ParserException::fromEmptyExpression(
                line: $stream->current()->line,
            );
        }

        $expressionTokens = [];

        while (!$stream->currentIs(BuiltinTokenNames::END->name)) {
            $expressionTokens[] = $stream->current();

            $stream->consume();
        }

        $stream->expect(BuiltinTokenNames::END->name);

        $condition = $parser->expressionParser->parse(
            stream: new TokenStream(
                tokens: $expressionTokens,
            ),
            startingLine: $stream->tokens[$stream->position - 1]->line,
        );

        $parser->state->enterCondition();

        $bodyTokens = [];
        $elseTokens = [];
        $branches = [];
        $parsingElse = false;

        while (!$stream->eof()) {
            if ($stream->currentIs(BuiltinTokenNames::IF->name)) {
                $parser->state->enterCondition();

                if ($parsingElse) {
                    $elseTokens[] = $stream->current();
                } else {
                    $bodyTokens[] = $stream->current();
                }

                $stream->consume();

                continue;
            }

            if ($stream->currentIs(BuiltinTokenNames::ENDIF->name)) {
                $parser->state->leaveCondition();

                if ($parser->state->conditionDepth === 0) {
                    $stream->consume();

                    break;
                }

                if ($parsingElse) {
                    $elseTokens[] = $stream->current();
                } else {
                    $bodyTokens[] = $stream->current();
                }

                $stream->consume();

                continue;
            }

            if ($stream->currentIs(BuiltinTokenNames::ELSEIF->name)) {
                $stream->consume();

                $branchTokens = [];

                while (!$stream->currentIs(BuiltinTokenNames::END->name)) {
                    $branchTokens[] = $stream->current();

                    $stream->consume();
                }

                $stream->expect(BuiltinTokenNames::END->name);

                $branchCondition = $parser->expressionParser->parse(
                    stream: new TokenStream(
                        tokens: $branchTokens,
                    ),
                    startingLine: $stream->tokens[$stream->position - 1]->line,
                );

                $branchBodyTokens = [];

                while (
                    !$stream->currentIs(BuiltinTokenNames::ELSEIF->name) &&
                    !$stream->currentIs(BuiltinTokenNames::ELSE->name) &&
                    !$stream->currentIs(BuiltinTokenNames::ENDIF->name)
                ) {
                    $branchBodyTokens[] = $stream->current();

                    $stream->consume();
                }

                $parser->state->enterCondition();

                $branches[] = new ConditionalBranchNode(
                    operand: $branchCondition,
                    body: $parser->parse(
                        stream: new TokenStream(
                            tokens: $branchBodyTokens,
                        ),
                    )->nodes,
                );

                $parser->state->leaveCondition();

                continue;
            }

            if ($stream->currentIs(BuiltinTokenNames::ELSE->name)) {
                $stream->expect(BuiltinTokenNames::ELSE->name);

                $parsingElse = true;

                continue;
            }

            if ($parsingElse) {
                $elseTokens[] = $stream->current();
            } else {
                $bodyTokens[] = $stream->current();
            }

            $stream->consume();
        }

        if (\sizeof($bodyTokens) > 0) {
            $parser->state->enterCondition();

            $body = $parser->parse(
                stream: new TokenStream(
                    tokens: $bodyTokens,
                ),
            )->nodes;

            $parser->state->leaveCondition();
        }

        if (\count($elseTokens) > 0) {
            $parser->state->enterCondition();

            $elseBody = $parser->parse(
                stream: new TokenStream(
                    tokens: $elseTokens,
                ),
            )->nodes;

            $parser->state->leaveCondition();
        }

        return [
            new ConditionalNode(
                operand: $condition,
                body: $body ?? [],
                branches: $branches,
                else: $elseBody ?? [],
            ),
        ];
    }
}
