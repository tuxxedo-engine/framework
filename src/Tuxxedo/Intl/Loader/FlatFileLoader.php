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

namespace Tuxxedo\Intl\Loader;

use Tuxxedo\Intl\Locale;
use Tuxxedo\Intl\TranslationException;

class FlatFileLoader implements PhraseLoaderInterface
{
    public function __construct(
        private readonly string $rootDirectory,
    ) {
    }

    public function load(
        Locale $locale,
        string $group,
    ): ?array {
        if (\preg_match('/^[A-Za-z0-9_-]+$/', $group) !== 1) {
            return null;
        }

        $file = $this->rootDirectory . '/' . $locale->tag . '/' . $group . '.php';

        if (!\is_file($file)) {
            return null;
        }

        $contents = require $file;

        if (!\is_array($contents)) {
            throw TranslationException::fromMalformedCatalogue(
                file: $file,
            );
        }

        return self::normalize(
            file: $file,
            source: $contents,
        );
    }

    /**
     * @param array<array-key, mixed> $source
     *
     * @return array<string, mixed>
     */
    private static function normalize(
        string $file,
        array $source,
    ): array {
        $result = [];

        foreach ($source as $key => $value) {
            if (!\is_string($key)) {
                throw TranslationException::fromNonStringKey(
                    file: $file,
                );
            }

            if (\is_array($value)) {
                $value = self::normalize(
                    file: $file,
                    source: $value,
                );
            } elseif (!\is_string($value)) {
                throw TranslationException::fromNonStringValue(
                    file: $file,
                    key: $key,
                );
            }

            if (\str_contains($key, '.')) {
                self::mergePath(
                    file: $file,
                    target: $result,
                    path: \explode('.', $key),
                    value: $value,
                );

                continue;
            }

            self::mergeSegment(
                file: $file,
                target: $result,
                segment: $key,
                value: $value,
            );
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $target
     * @param list<string> $path
     */
    private static function mergePath(
        string $file,
        array &$target,
        array $path,
        mixed $value,
    ): void {
        $segment = \array_shift($path);

        if ($segment === null || $segment === '') {
            throw TranslationException::fromEmptyKeySegment(
                file: $file,
            );
        }

        if ($path === []) {
            self::mergeSegment(
                file: $file,
                target: $target,
                segment: $segment,
                value: $value,
            );

            return;
        }

        if (!\array_key_exists($segment, $target)) {
            $target[$segment] = [];
        } elseif (!\is_array($target[$segment])) {
            throw TranslationException::fromKeyCollision(
                file: $file,
                segment: $segment,
            );
        }

        /** @var array<string, mixed> $child */
        $child = $target[$segment];

        self::mergePath(
            file: $file,
            target: $child,
            path: $path,
            value: $value,
        );

        $target[$segment] = $child;
    }

    /**
     * @param array<string, mixed> $target
     */
    private static function mergeSegment(
        string $file,
        array &$target,
        string $segment,
        mixed $value,
    ): void {
        if (!\array_key_exists($segment, $target)) {
            $target[$segment] = $value;

            return;
        }

        $existing = $target[$segment];

        if (\is_array($existing) && \is_array($value)) {
            /** @var array<string, mixed> $merged */
            $merged = $existing;

            foreach ($value as $childKey => $childValue) {
                self::mergeSegment(
                    file: $file,
                    target: $merged,
                    segment: (string) $childKey,
                    value: $childValue,
                );
            }

            $target[$segment] = $merged;

            return;
        }

        if ($existing !== $value) {
            throw TranslationException::fromKeyCollision(
                file: $file,
                segment: $segment,
            );
        }
    }
}
