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
use Tuxxedo\View\Lumi\Node\ConditionalBranchNode;
use Tuxxedo\View\Lumi\Node\ConditionalNode;
use Tuxxedo\View\Lumi\Node\NodeInterface;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Parser\ParserInterface;
use Tuxxedo\View\Lumi\Token\BuiltinTokenNames;

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
            throw ParserException::fromEmptyExpression();
        }

        $tokens = [];

        do {
            $tokens[] = $stream->current();

            $stream->consume();
        } while (!$stream->currentIs(BuiltinTokenNames::END->name));

        $stream->expect(BuiltinTokenNames::END->name);

        $condition = $parser->expressionParser->parse(
            stream: new TokenStream(tokens: $tokens),
            state: $parser->state,
        );

        $parser->state->enterCondition();

        $bodyTokens = [];
        $branches = [];
        $elseTokens = [];

        $currentTarget = &$bodyTokens;

        while (!$stream->eof()) {
            if ($stream->currentIs(BuiltinTokenNames::IF->name)) {
                $parser->state->enterCondition();

                $currentTarget[] = $stream->current();

                $stream->consume();

                continue;
            }

            if ($stream->currentIs(BuiltinTokenNames::ENDIF->name)) {
                $parser->state->leaveCondition();

                if ($parser->state->conditionDepth === 0) {
                    $stream->consume();

                    break;
                }

                $currentTarget[] = $stream->current();

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
                    stream: new TokenStream(tokens: $branchTokens),
                    state: $parser->state,
                );

                $branchBody = [];

                while (
                    !$stream->currentIs(BuiltinTokenNames::ELSEIF->name) &&
                    !$stream->currentIs(BuiltinTokenNames::ELSE->name) &&
                    !$stream->currentIs(BuiltinTokenNames::ENDIF->name)
                ) {
                    $branchBody[] = $stream->current();
                    $stream->consume();
                }

                $branches[] = new ConditionalBranchNode(
                    operand: $branchCondition,
                    body: $parser->parse(
                        stream: new TokenStream(
                            tokens: $branchBody,
                        ),
                    )->nodes,
                );

                continue;
            }

            if ($stream->currentIs(BuiltinTokenNames::ELSE->name)) {
                $stream->expect(BuiltinTokenNames::ELSE->name);

                $currentTarget = &$elseTokens;

                continue;
            }

            $currentTarget[] = $stream->current();

            $stream->consume();
        }

        return [
            new ConditionalNode(
                operand: $condition,
                body: $parser->parse(
                    stream: new TokenStream(
                        tokens: $bodyTokens,
                    ),
                )->nodes,
                branches: $branches,
                else: $parser->parse(
                    stream: new TokenStream(
                        tokens: $elseTokens,
                    ),
                )->nodes,
            ),
        ];
    }
}
