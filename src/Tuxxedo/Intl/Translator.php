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

namespace Tuxxedo\Intl;

use Tuxxedo\Intl\Config\IntlConfigInterface;
use Tuxxedo\Intl\Loader\CachingLoader;
use Tuxxedo\Intl\Loader\PhraseLoaderInterface;
use Tuxxedo\Intl\Resolver\LocaleResolverInterface;

class Translator implements TranslatorInterface
{
    private readonly CachingLoader $loader;

    private readonly ?LocaleInterface $localeOverride;

    public LocaleInterface $locale {
        get {
            return $this->localeOverride ?? $this->resolver->currentLocale();
        }
    }

    /**
     * @var list<LocaleInterface>
     */
    public array $supportedLocales {
        get {
            $locales = [
                $this->config->defaultLocale,
            ];

            foreach ($this->config->supportedLocales as $supported) {
                if ($supported->tag !== $this->config->defaultLocale->tag) {
                    $locales[] = $supported;
                }
            }

            return $locales;
        }
    }

    public function __construct(
        private readonly IntlConfigInterface $config,
        PhraseLoaderInterface $loader,
        private readonly LocaleResolverInterface $resolver,
        private readonly PluralSelector $pluralSelector,
    ) {
        $this->loader = new CachingLoader(
            loader: $loader,
        );

        $this->localeOverride = null;
    }

    public function translate(
        string $key,
        array $replacements = [],
        ?LocaleInterface $locale = null,
    ): string {
        $effective = $locale ?? $this->locale;

        $value = $this->resolveKey(
            key: $key,
            locale: $effective,
        );

        if ($value === null) {
            return $this->applyMissingKey(
                key: $key,
                locale: $effective,
            );
        }

        return self::interpolate(
            template: $value,
            replacements: $replacements,
            isolate: $effective->direction === LocaleDirection::RTL,
        );
    }

    public function plural(
        string $key,
        int $count,
        array $replacements = [],
        ?LocaleInterface $locale = null,
    ): string {
        $effective = $locale ?? $this->locale;
        $value = null;

        $path = self::splitKey(
            key: $key,
        );

        if ($path !== null) {
            foreach ($this->candidateLocales($effective) as $candidate) {
                $value = $this->lookupIn(
                    locale: $candidate,
                    group: $path[0],
                    segments: [
                        ...$path[1],
                        $this->pluralSelector->select(
                            locale: $candidate,
                            count: $count,
                        )->value,
                    ],
                );

                if ($value !== null) {
                    break;
                }
            }
        }

        if ($value === null) {
            return $this->applyMissingPluralSibling(
                key: $key,
                category: $this->pluralSelector->select(
                    locale: $effective,
                    count: $count,
                ),
                locale: $effective,
            );
        }

        return self::interpolate(
            template: $value,
            replacements: $replacements,
            isolate: $effective->direction === LocaleDirection::RTL,
        );
    }

    public function has(
        string $key,
        ?LocaleInterface $locale = null,
    ): bool {
        return $this->resolveKey(
            key: $key,
            locale: $locale ?? $this->locale,
        ) !== null;
    }

    public function sourceOf(
        string $key,
        ?LocaleInterface $locale = null,
    ): ?string {
        $path = self::splitKey(
            key: $key,
        );

        if ($path === null) {
            return null;
        }

        foreach ($this->candidateLocales($locale ?? $this->locale) as $candidate) {
            $found = $this->lookupIn(
                locale: $candidate,
                group: $path[0],
                segments: $path[1],
            ) !== null;

            if ($found) {
                return $this->loader->sourceOf(
                    locale: $candidate,
                    group: $path[0],
                    segments: $path[1],
                );
            }
        }

        return null;
    }

    public function withLocale(
        LocaleInterface $locale,
    ): self {
        return clone (
            $this,
            [
                'localeOverride' => $locale,
            ],
        );
    }

    /**
     * @return iterable<int, LocaleInterface>
     */
    private function candidateLocales(
        LocaleInterface $locale,
    ): iterable {
        $seen = [];

        foreach ([$locale, $this->config->defaultLocale] as $root) {
            foreach ($root->fallbackChain() as $candidate) {
                if (isset($seen[$candidate->tag])) {
                    continue;
                }

                $seen[$candidate->tag] = true;

                yield $candidate;
            }
        }
    }

    /**
     * @return array{string, list<string>}|null
     */
    private static function splitKey(
        string $key,
    ): ?array {
        if ($key === '') {
            return null;
        }

        $segments = \explode('.', $key);

        if (\in_array('', $segments, true)) {
            return null;
        }

        $group = \array_shift($segments);

        return [
            $group,
            $segments,
        ];
    }

    private function resolveKey(
        string $key,
        LocaleInterface $locale,
    ): ?string {
        $path = self::splitKey(
            key: $key,
        );

        if ($path === null) {
            return null;
        }

        foreach ($this->candidateLocales($locale) as $candidate) {
            $value = $this->lookupIn(
                locale: $candidate,
                group: $path[0],
                segments: $path[1],
            );

            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param list<string> $segments
     */
    private function lookupIn(
        LocaleInterface $locale,
        string $group,
        array $segments,
    ): ?string {
        $catalogue = $this->loader->load(
            locale: $locale,
            group: $group,
        );

        if ($catalogue === null) {
            return null;
        }

        $value = self::lookup(
            catalogue: $catalogue,
            segments: $segments,
        );

        return \is_string($value)
            ? $value
            : null;
    }

    /**
     * @param array<string, mixed> $catalogue
     * @param list<string> $segments
     */
    private static function lookup(
        array $catalogue,
        array $segments,
    ): mixed {
        $cursor = $catalogue;

        foreach ($segments as $segment) {
            if (!\is_array($cursor) || !\array_key_exists($segment, $cursor)) {
                return null;
            }

            $cursor = $cursor[$segment];
        }

        return $cursor;
    }

    private function applyMissingKey(
        string $key,
        LocaleInterface $locale,
    ): string {
        return match ($this->config->missingKeyMode) {
            MissingKeyMode::STRICT => throw TranslationException::fromMissingKey(
                key: $key,
                locale: $locale,
            ),
            MissingKeyMode::PASSTHROUGH => $key,
            MissingKeyMode::MARKER => '[missing:' . $key . ']',
        };
    }

    private function applyMissingPluralSibling(
        string $key,
        CldrCategory $category,
        LocaleInterface $locale,
    ): string {
        return match ($this->config->missingKeyMode) {
            MissingKeyMode::STRICT => throw TranslationException::fromMissingPluralSibling(
                key: $key,
                category: $category,
                locale: $locale,
            ),
            MissingKeyMode::PASSTHROUGH => $key,
            MissingKeyMode::MARKER => '[missing_plural:' . $key . ':' . $category->value . ']',
        };
    }

    /**
     * @param array<string, scalar|null> $replacements
     */
    private static function interpolate(
        string $template,
        array $replacements,
        bool $isolate,
    ): string {
        $length = \strlen($template);
        $buffer = '';
        $position = 0;

        while ($position < $length) {
            $char = $template[$position];

            if ($char === '\\' && $position + 1 < $length) {
                $next = $template[$position + 1];

                if ($next === '{' || $next === '}' || $next === '\\') {
                    $buffer .= $next;
                    $position += 2;

                    continue;
                }
            }

            if ($char === '{') {
                $closing = \strpos($template, '}', $position + 1);

                if ($closing !== false) {
                    $name = \substr($template, $position + 1, $closing - $position - 1);

                    if ($name !== '' && \array_key_exists($name, $replacements)) {
                        $value = $replacements[$name];
                        $position = $closing + 1;

                        $string = $value === null
                            ? ''
                            : (string) $value;

                        $buffer .= $isolate && $string !== ''
                            ? "\u{2068}" . $string . "\u{2069}"
                            : $string;

                        continue;
                    }
                }
            }

            $buffer .= $char;

            ++$position;
        }

        return $buffer;
    }
}
