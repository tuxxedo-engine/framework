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

namespace Tuxxedo\Cache;

use Tuxxedo\Cache\Config\CacheDriverConfigInterface;
use Tuxxedo\Exception;

class CacheException extends Exception
{
    public static function fromMissingConfig(): self
    {
        return new self(
            message: 'Unable to create cache: no cache configuration is bound',
        );
    }

    public static function fromDriverUnavailable(
        string $driver,
        string $reason,
    ): self {
        return new self(
            message: \sprintf(
                'Unable to create cache driver "%s": %s',
                $driver,
                $reason,
            ),
        );
    }

    public static function fromDriverFailure(
        string $driver,
        string $operation,
        string $key,
    ): self {
        return new self(
            message: \sprintf(
                'Cache driver "%s" failed to %s key "%s"',
                $driver,
                $operation,
                $key,
            ),
        );
    }

    /**
     * @codeCoverageIgnore
     */
    public static function fromDriverError(
        string $driver,
        string $operation,
        string $key,
        \Throwable $previous,
    ): self {
        return new self(
            message: \sprintf(
                'Cache driver "%s" failed to %s key "%s": %s',
                $driver,
                $operation,
                $key,
                $previous->getMessage(),
            ),
            previous: $previous,
        );
    }

    /**
     * @param class-string<CacheDriverConfigInterface> $expected
     * @param class-string<CacheDriverConfigInterface> $actual
     *
     * @codeCoverageIgnore
     */
    public static function fromInvalidDriverConfig(
        string $driver,
        string $expected,
        string $actual,
    ): self {
        return new self(
            message: \sprintf(
                'Unable to create cache driver "%s": expected a configuration implementing "%s", got "%s"',
                $driver,
                self::formatShortClass($expected),
                self::formatShortClass($actual),
            ),
        );
    }

    public static function fromInvalidNamespace(): self
    {
        return new self(
            message: 'Invalid cache driver configuration: the namespace must not be empty',
        );
    }

    public static function fromInvalidSeparator(
        string $separator,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid cache configuration: separator "%s" must be a single printable character that cannot appear in a scope name',
                $separator,
            ),
        );
    }

    public static function fromInvalidScopeName(
        string $scope,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid cache configuration: scope name "%s" may only contain letters, digits, "_", "." and "-"',
                $scope,
            ),
        );
    }

    public static function fromDuplicateScope(
        string $scope,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid cache configuration: scope "%s" is declared more than once',
                $scope,
            ),
        );
    }

    public static function fromScopeClash(
        string $scope,
        string $other,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid cache configuration: scope "%s" clashes with scope "%s"',
                $scope,
                $other,
            ),
        );
    }

    public static function fromUnknownScope(
        string $scope,
    ): self {
        return new self(
            message: \sprintf(
                'Unable to find cache scope: scope "%s" is not declared',
                $scope,
            ),
        );
    }

    public static function fromInvalidKey(
        string $key,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid cache key "%s": keys must be 1 to 255 bytes without control characters',
                \addcslashes($key, "\0..\37\177"),
            ),
        );
    }

    public static function fromInvalidTtl(
        string $key,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid TTL for cache key "%s": the duration must be positive',
                $key,
            ),
        );
    }

    public static function fromInvalidStep(
        string $key,
        int $by,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid step for cache key "%s": %d must be greater than zero',
                $key,
                $by,
            ),
        );
    }

    public static function fromUnsupportedValue(
        string $key,
        string $type,
    ): self {
        return new self(
            message: \sprintf(
                'Unable to store cache key "%s": values of type "%s" are not storable',
                $key,
                $type,
            ),
        );
    }

    public static function fromTypeMismatch(
        string $key,
        string $expected,
        string $actual,
    ): self {
        return new self(
            message: \sprintf(
                'Unable to read cache key "%s": expected %s, found %s',
                $key,
                $expected,
                $actual,
            ),
        );
    }

    public static function fromCounterOverflow(
        string $key,
        string $operation,
    ): self {
        return new self(
            message: \sprintf(
                'Unable to %s cache key "%s": the counter would overflow',
                $operation,
                $key,
            ),
        );
    }

    /**
     * @codeCoverageIgnore
     */
    public static function fromInvalidEnum(
        string $key,
        string $enum,
    ): self {
        return new self(
            message: \sprintf(
                'Unable to read cache key "%s": "%s" is not an enum',
                $key,
                $enum,
            ),
        );
    }
}
