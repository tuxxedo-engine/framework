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

namespace Tuxxedo\View\Lumi\Lexer\Handler;

use Tuxxedo\View\Lumi\Lexer\Expression\ExpressionLexerInterface;
use Tuxxedo\View\Lumi\Lexer\LexerException;
use Tuxxedo\View\Lumi\Lexer\LexerStateInterface;
use Tuxxedo\View\Lumi\Syntax\Token\TokenInterface;

interface TokenHandlerInterface
{
    public function getStartingSequence(): string;
    public function getEndingSequence(): string;

    /**
     * @return TokenInterface[]
     *
     * @throws LexerException
     */
    public function tokenize(
        int $startingLine,
        string $buffer,
        ExpressionLexerInterface $expressionLexer,
        LexerStateInterface $state,
    ): array;
}
