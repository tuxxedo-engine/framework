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

namespace Tuxxedo\Console\Invocation;

readonly class ParsedArgv implements ParsedArgvInterface
{
    /**
     * @param list<string> $positionals
     * @param array<string, list<string>> $options
     * @param array<string, bool> $flags
     */
    public function __construct(
        public array $positionals,
        public array $options,
        public array $flags,
    ) {
    }
}
