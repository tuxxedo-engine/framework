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
use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Parser\ParserInterface;
use Tuxxedo\View\Lumi\Syntax\Node\ContinueNode;
use Tuxxedo\View\Lumi\Syntax\Token\ContinueToken;

class ContinueParserHandler implements ParserHandlerInterface
{
    public private(set) string $tokenClassName = ContinueToken::class;

    public function parse(
        ParserInterface $parser,
        TokenStreamInterface $stream,
    ): array {
        $token = $stream->current();

        $stream->consume();

        if ($parser->state->loopDepth === 0) {
            throw ParserException::fromUnexpectedContinueOutsideLoop(
                line: $token->line,
            );
        } elseif (
            $token->op1 !== null &&
            \intval($token->op1) > $parser->state->loopDepth
        ) {
            throw ParserException::fromUnexpectedContinueOutOfBounds(
                level: \intval($token->op1),
                maxLevel: $parser->state->loopDepth,
                line: $token->line,
            );
        }

        return [
            new ContinueNode(
                count: $token->op1 !== null
                    ? (int) $token->op1
                    : null,
            ),
        ];
    }
}
