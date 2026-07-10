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

namespace Support\Env\Source;

use Tuxxedo\Env\EnvException;
use Tuxxedo\Env\Source\EnvSourceInterface;

class StubEnvSource implements EnvSourceInterface
{
    /**
     * @param array<string, string|int|float|bool> $values
     */
    public function __construct(
        private readonly array $values,
    ) {
    }

    public function has(
        string $key,
    ): bool {
        return \array_key_exists($key, $this->values);
    }

    public function get(
        string $key,
    ): string|int|float|bool {
        if (!\array_key_exists($key, $this->values)) {
            throw EnvException::fromMissingKey(
                key: $key,
            );
        }

        return $this->values[$key];
    }
}
