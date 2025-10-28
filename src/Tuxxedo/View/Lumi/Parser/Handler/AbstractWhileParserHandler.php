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
use Tuxxedo\View\Lumi\Syntax\Node\ExpressionNodeInterface;
use Tuxxedo\View\Lumi\Syntax\Token\EndToken;

abstract class AbstractWhileParserHandler implements ParserHandlerInterface
{
    /**
     * @throws ParserException
     */
    protected function collectCondition(
        ParserInterface $parser,
        TokenStreamInterface $stream,
    ): ExpressionNodeInterface {
        if ($stream->currentIs(EndToken::class)) {
            throw ParserException::fromEmptyExpression(
                line: $stream->current()->line,
            );
        }

        $tokens = [];

        do {
            $tokens[] = $stream->current();

            $stream->consume();
        } while (!$stream->currentIs(EndToken::class));

        $stream->expect(EndToken::class);

        return $parser->expressionParser->parse(
            stream: new TokenStream(
                tokens: $tokens,
            ),
            startingLine: $stream->tokens[$stream->position - 1]->line,
        );
    }
}
