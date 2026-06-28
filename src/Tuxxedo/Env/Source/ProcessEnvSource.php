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

namespace Tuxxedo\Env\Source;

use Tuxxedo\Env\EnvException;

class ProcessEnvSource implements EnvSourceInterface
{
    public function has(
        string $key,
    ): bool {
        return \getenv($key) !== false;
    }

    public function get(
        string $key,
    ): string|int|float|bool {
        $value = \getenv($key);

        if ($value === false) {
            throw EnvException::fromMissingKey(
                key: $key,
            );
        }

        return $value;
    }
}
