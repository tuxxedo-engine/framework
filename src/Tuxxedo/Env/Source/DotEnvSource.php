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

use Tuxxedo\Env\DotEnvParser;
use Tuxxedo\Env\EnvException;

class DotEnvSource implements EnvSourceInterface
{
    /**
     * @var array<string, string|int|float|bool>
     */
    private array $values;

    public function __construct(
        string $file,
    ) {
        if (!\is_file($file)) {
            throw EnvException::fromMissingFile(
                file: $file,
            );
        }

        $contents = @\file_get_contents($file);

        if ($contents === false) {
            throw EnvException::fromMissingFile(
                file: $file,
            );
        }

        $this->values = (new DotEnvParser())->parse(
            contents: $contents,
            file: $file,
        );
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
