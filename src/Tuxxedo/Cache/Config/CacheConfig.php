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

namespace Tuxxedo\Cache\Config;

use Tuxxedo\Cache\CacheException;
use Tuxxedo\Config\Attribute\ConfigNamespace;

#[ConfigNamespace('cache')]
class CacheConfig implements CacheConfigInterface
{
    /**
     * @param list<CacheScopeInterface> $scopes
     *
     * @throws CacheException
     */
    public function __construct(
        public readonly CacheDriverConfigInterface $default,
        public readonly array $scopes = [],
        public readonly ?string $separator = ':',
    ) {
        $this->validate();
    }

    /**
     * @throws CacheException
     */
    private function validate(): void
    {
        $separator = $this->separator;

        if (
            $separator !== null &&
            \preg_match('/\A[^A-Za-z0-9_.\-\x00-\x20\x7F]\z/', $separator) !== 1
        ) {
            throw CacheException::fromInvalidSeparator($separator);
        }

        $names = [];

        foreach ($this->scopes as $scope) {
            if (\preg_match('/\A[A-Za-z0-9_.\-]+\z/', $scope->name) !== 1) {
                throw CacheException::fromInvalidScopeName($scope->name);
            }

            foreach ($names as $name) {
                if ($name === $scope->name) {
                    throw CacheException::fromDuplicateScope($scope->name);
                }

                if (
                    \str_starts_with($name, $scope->name) ||
                    \str_starts_with($scope->name, $name)
                ) {
                    throw CacheException::fromScopeClash(
                        scope: $scope->name,
                        other: $name,
                    );
                }
            }

            $names[] = $scope->name;
        }
    }
}
