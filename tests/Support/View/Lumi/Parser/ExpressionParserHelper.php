<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2026 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Support\View\Lumi\Parser;

use Tuxxedo\View\Lumi\Lexer\Expression\ExpressionLexer;
use Tuxxedo\View\Lumi\Lexer\TokenStream;
use Tuxxedo\View\Lumi\Parser\Expression\ExpressionParser;
use Tuxxedo\View\Lumi\Syntax\Node\ExpressionNodeInterface;
use Tuxxedo\View\Lumi\Syntax\Token\TokenInterface;

class ExpressionParserHelper
{
    private readonly ExpressionLexer $expressionLexer;
    private readonly ExpressionParser $expressionParser;

    public function __construct()
    {
        $this->expressionLexer = new ExpressionLexer();
        $this->expressionParser = new ExpressionParser();
    }

    public function parse(
        string $expression,
        int $startingLine = 1,
    ): ExpressionNodeInterface {
        return $this->expressionParser->parse(
            stream: new TokenStream(
                tokens: $this->expressionLexer->lex(
                    startingLine: $startingLine,
                    operand: $expression,
                ),
            ),
            startingLine: $startingLine,
        );
    }

    /**
     * @param TokenInterface[] $tokens
     */
    public function parseTokens(
        array $tokens,
        int $startingLine = 1,
    ): ExpressionNodeInterface {
        return $this->expressionParser->parse(
            stream: new TokenStream(
                tokens: $tokens,
            ),
            startingLine: $startingLine,
        );
    }
}
