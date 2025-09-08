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

namespace Tuxxedo\View\Lumi\Parser;

readonly class ParserStateProperties implements ParserStatePropertiesInterface
{
    /**
     * @param array<string, string|int|bool> $state
     */
    final public function __construct(
        public int $conditionDepth,
        public array $state,
    ) {
    }

    public static function fromState(
        ParserStateInterface $state,
    ): static {
        return new static(
            conditionDepth: $state->conditionDepth,
            state: $state->state,
        );
    }
}
