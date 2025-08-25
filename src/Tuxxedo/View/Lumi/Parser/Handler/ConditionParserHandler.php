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
            stream: new TokenStream(
                tokens: $tokens,
            ),
            state: $parser->state,
        );

        $body = [];

        $parser->state->enterCondition();

        while (
            !$stream->currentIs(BuiltinTokenNames::ENDIF->name) ||
            $parser->state->conditionDepth > 0 ||
            !$stream->eof()
        ) {
            if ($stream->currentIs(BuiltinTokenNames::IF->name)) {
                $parser->state->enterCondition();
            } elseif ($stream->currentIs(BuiltinTokenNames::ENDIF->name)) {
                $parser->state->leaveCondition();

                if ($parser->state->conditionDepth === 0) {
                    $stream->consume();

                    break;
                }
            }

            $body[] = $stream->current();

            $stream->consume();
        }

        return [
            new ConditionalNode(
                operand: $condition,
                body: $parser->parse(
                    stream: new TokenStream(
                        tokens: $body,
                    ),
                )->nodes,
            ),
        ];
    }
}
