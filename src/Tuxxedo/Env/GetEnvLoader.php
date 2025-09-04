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

namespace Tuxxedo\Env;

class GetEnvLoader implements EnvLoaderInterface
{
    public function has(
        string $variable,
    ): bool {
        return \getenv($variable) !== false;
    }

    public function value(
        string $variable,
    ): string {
        $value = \getenv($variable);

        if ($value === false) {
            throw EnvException::fromInvalidVariable(
                variable: $variable,
            );
        }

        return $value;
    }
}
