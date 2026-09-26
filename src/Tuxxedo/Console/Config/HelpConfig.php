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

namespace Tuxxedo\Console\Config;

class HelpConfig
{
    /**
     * @param list<string> $tokens
     */
    public function __construct(
        public readonly array $tokens,
    ) {
    }

    public static function default(): self
    {
        return new self(
            tokens: [
                '--help',
                '-h',
            ],
        );
    }
}
