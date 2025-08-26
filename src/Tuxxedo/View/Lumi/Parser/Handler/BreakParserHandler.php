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

use Tuxxedo\View\Lumi\Lexer\TokenStreamInterface;
use Tuxxedo\View\Lumi\Node\BreakNode;
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Parser\ParserInterface;
use Tuxxedo\View\Lumi\Token\BuiltinTokenNames;

class BreakParserHandler implements ParserHandlerInterface
{
    public private(set) string $tokenName = BuiltinTokenNames::BREAK->name;

    public function parse(
        ParserInterface $parser,
        TokenStreamInterface $stream,
    ): array {
        $token = $stream->current();
        $stream->consume();

        if ($parser->state->loopDepth === 0) {
            throw ParserException::fromUnexpectedBreakOutsideLoop();
        } elseif (
            $token->op1 !== null &&
            \intval($token->op1) > $parser->state->loopDepth
        ) {
            throw ParserException::fromUnexpectedBreakOutOfBounds(
                level: \intval($token->op1),
                maxLevel: $parser->state->loopDepth,
            );
        }

        return [
            new BreakNode(
                count: $token->op1 !== null
                    ? (int) $token->op1
                    : null,
            ),
        ];
    }
}
