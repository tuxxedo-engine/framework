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

namespace Tuxxedo\View\Lumi\Syntax;

use Tuxxedo\View\Lumi\Parser\ParserException;
use Tuxxedo\View\Lumi\Syntax\Token\TokenInterface;

interface SymbolInterface
{
    /**
     * @return string[]
     */
    public static function all(): array;

    public static function is(
        TokenInterface $token
    ): bool;

    /**
     * @throws ParserException
     */
    public static function from(
        TokenInterface $token,
    ): static;

    public function symbol(): string;
}
