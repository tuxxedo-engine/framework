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

namespace Tuxxedo\View\Lumi\Lexer\Expression;

use Tuxxedo\View\Lumi\Syntax\Token\TokenInterface;

interface ExpressionLexerInterface
{
    /**
     * @return TokenInterface[]
     */
    public function parse(
        int $startingLine,
        string $operand,
    ): array;
}
