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

namespace Fixture\View\Lumi\Lexer\Handler\BlockTokenHandler;

use Tuxxedo\View\Lumi\Lexer\Expression\ExpressionLexerInterface;
use Tuxxedo\View\Lumi\Lexer\Handler\Block\BlockHandlerState;
use Tuxxedo\View\Lumi\Lexer\LexerStateInterface;

trait SpyBlockHandlerTrait
{
    public ?int $lastStartingLine = null;
    public ?string $lastExpression = null;
    public ?BlockHandlerState $lastBlockState = null;
    public int $callCount = 0;

    /**
     * @return array{}
     */
    public function lex(
        int $startingLine,
        string $expression,
        ExpressionLexerInterface $expressionLexer,
        LexerStateInterface $state,
        BlockHandlerState $blockState,
    ): array {
        $this->lastStartingLine = $startingLine;
        $this->lastExpression = $expression;
        $this->lastBlockState = $blockState;
        $this->callCount++;

        return [];
    }
}
