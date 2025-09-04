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

class Env implements EnvInterface
{
    final public function __construct(
        protected readonly EnvLoaderInterface $loader,
    ) {
    }

    public static function createFromEnvironment(): static
    {
        return new static(
            loader: new GetEnvLoader(),
        );
    }

    public function has(
        string $variable,
    ): bool {
        return $this->loader->has($variable);
    }

    /**
     * @throws EnvException
     */
    protected function value(
        string $variable,
    ): string {
        return $this->loader->value($variable);
    }

    public function getInt(
        string $variable,
    ): int {
        return (int) $this->value($variable);
    }

    public function getBool(
        string $variable,
    ): bool {
        $value = \mb_strtolower($this->value($variable));

        if (
            $value === 'yes' ||
            $value === 'on' ||
            $value === 'true'
        ) {
            return true;
        }

        if (
            $value === 'no' ||
            $value === 'off' ||
            $value === 'false'
        ) {
            return false;
        }

        return (bool) $value;
    }

    public function getFloat(
        string $variable,
    ): float {
        return (float) $this->value($variable);
    }

    public function getString(
        string $variable,
    ): string {
        return $this->value($variable);
    }
}
