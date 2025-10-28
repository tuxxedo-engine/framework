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

namespace Tuxxedo\View\Lumi\Syntax\Token;

abstract readonly class AbstractToken implements TokenInterface
{
    public static function name(): string
    {
        /** @var string $name */
        $name = \strrchr(static::class, '\\');

        return \strtoupper(
            \substr($name, 1, -5),
        );
    }
}
