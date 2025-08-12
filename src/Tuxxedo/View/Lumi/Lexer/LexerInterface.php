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

namespace Tuxxedo\View\Lumi\Lexer;

use Tuxxedo\View\Lumi\Lexer\Tokens\TokenInterface;

interface LexerInterface
{
    /**
     * @return TokenInterface[]
     *
     * @throws LexerException
     */
    public function tokenizeByString(
        string $sourceCode,
    ): array;

    /**
     * @return TokenInterface[]
     *
     * @throws LexerException
     */
    public function tokenizeByFile(
        string $sourceFile,
    ): array;
}
