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

namespace Fixture\View\Lumi\LumiConfigurator;

use Tuxxedo\Config\ConfigInterface;

class StubConfig implements ConfigInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private readonly array $data = [],
    ) {
    }

    public function has(
        string $path,
    ): bool {
        return \array_key_exists($path, $this->data);
    }

    public function isNull(
        string $path,
    ): bool {
        return $this->data[$path] === null;
    }

    public function path(
        string $path,
    ): mixed {
        return $this->data[$path] ?? null;
    }

    public function section(
        string $path,
    ): self {
        return $this;
    }

    public function getInt(
        string $path,
    ): int {
        $value = $this->data[$path] ?? 0;

        return \is_scalar($value) ? (int) $value : 0;
    }

    public function isInt(
        string $path,
    ): bool {
        return \is_int($this->data[$path] ?? null);
    }

    public function getBool(
        string $path,
    ): bool {
        $value = $this->data[$path] ?? false;

        return \is_scalar($value) ? (bool) $value : false;
    }

    public function isBool(
        string $path,
    ): bool {
        return \is_bool($this->data[$path] ?? null);
    }

    public function getFloat(
        string $path,
    ): float {
        $value = $this->data[$path] ?? 0.0;

        return \is_scalar($value) ? (float) $value : 0.0;
    }

    public function isFloat(
        string $path,
    ): bool {
        return \is_float($this->data[$path] ?? null);
    }

    public function getString(
        string $path,
    ): string {
        $value = $this->data[$path] ?? '';

        return \is_scalar($value) ? (string) $value : '';
    }

    public function isString(
        string $path,
    ): bool {
        return \is_string($this->data[$path] ?? null);
    }

    /**
     * @template TEnum of \UnitEnum
     *
     * @param class-string<TEnum> $enum
     * @return TEnum
     */
    public function getEnum(
        string $path,
        string $enum,
    ): object {
        /** @var TEnum */
        return $this->data[$path];
    }
}
