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

namespace Tuxxedo\View\Lumi\Lexer\Handler\Block;

use Tuxxedo\View\Lumi\Lexer\LexerException;

abstract class AbstractLoopConstructHandler implements BlockHandlerInterface
{
    /**
     * @return numeric-string|null
     *
     * @throws LexerException
     */
    protected function lexDepth(
        int $startingLine,
        string $expression,
    ): ?string {
        $expression = \mb_trim($expression);

        if ($expression === '') {
            return null;
        }

        if (\preg_match('/^[1-9][0-9]*$/u', $expression) !== 1) {
            throw LexerException::fromInvalidLoopDepth(
                line: $startingLine,
            );
        }

        $depth = (string) (int) $expression;

        if ($depth === '1') {
            return null;
        }

        return $depth;
    }
}
