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

namespace Tuxxedo\Config;

class ConfigException extends \Exception
{
    public static function fromInvalidDirective(
        string $directive,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid configuration directive "%s"',
                $directive,
            ),
        );
    }

    public static function fromInvalidConfigKey(
        string $key,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid #[ConfigKey] value "%s": dot characters are not allowed, use a separate #[ConfigNamespace] class for nested paths',
                $key,
            ),
        );
    }

    public static function fromDuplicateConfigNamespace(
        string $namespace,
    ): self {
        return new self(
            message: \sprintf(
                'Duplicate configuration namespace "%s": a config entry has already been registered under this namespace',
                $namespace,
            ),
        );
    }

    public static function fromDuplicateConfigKey(
        string $path,
    ): self {
        return new self(
            message: \sprintf(
                'Duplicate configuration key "%s": both a typed configuration object and an existing directive provide this value',
                $path,
            ),
        );
    }

    public static function fromMissingAppConfig(
        \Throwable $previous,
    ): self {
        return new self(
            message: 'Missing app configuration: no config entry provides AppConfigInterface',
            previous: $previous,
        );
    }

    /**
     * @codeCoverageIgnore
     */
    public static function fromInvalidTypedConfigReturnType(
        string $file,
        string $returnType,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid typed configuration return type in "%s": closure return type "%s" is not a class or interface',
                $file,
                $returnType,
            ),
        );
    }
}
