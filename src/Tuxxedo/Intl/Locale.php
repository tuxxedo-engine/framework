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

class Locale implements LocaleInterface
{
    public readonly string $tag;

    private ?string $languageNameCache = null;

    private ?string $scriptNameCache = null;

    private ?string $regionNameCache = null;

    private ?string $displayNameCache = null;

    private ?LocaleDirection $directionCache = null;

    public string $languageName {
        get {
            return $this->languageNameCache ??= self::capitalize(
                name: \Locale::getDisplayLanguage($this->tag, $this->tag),
                tag: $this->tag,
            );
        }
    }

    public ?string $scriptName {
        get {
            if ($this->script === null) {
                return null;
            }

            return $this->scriptNameCache ??= self::capitalize(
                name: \Locale::getDisplayScript($this->tag, $this->tag),
                tag: $this->tag,
            );
        }
    }

    public ?string $regionName {
        get {
            if ($this->region === null) {
                return null;
            }

            return $this->regionNameCache ??= self::capitalize(
                name: \Locale::getDisplayRegion($this->tag, $this->tag),
                tag: $this->tag,
            );
        }
    }

    public string $displayName {
        get {
            return $this->displayNameCache ??= self::capitalize(
                name: \Locale::getDisplayName($this->tag, $this->tag),
                tag: $this->tag,
            );
        }
    }

    public LocaleDirection $direction {
        get {
            return $this->directionCache ??= \Locale::isRightToLeft($this->tag)
                ? LocaleDirection::RTL
                : LocaleDirection::LTR;
        }
    }

    /**
     * @param non-empty-string $language
     * @param non-empty-string|null $script
     * @param non-empty-string|null $region
     */
    private function __construct(
        public readonly string $language,
        public readonly ?string $script = null,
        public readonly ?string $region = null,
    ) {
        $segments = [
            $this->language,
        ];

        if ($this->script !== null) {
            $segments[] = $this->script;
        }

        if ($this->region !== null) {
            $segments[] = $this->region;
        }

        $this->tag = \join('-', $segments);
    }

    /**
     * @throws TranslationException
     */
    public static function parse(
        string $input,
    ): self {
        $normalized = \str_replace('_', '-', \mb_trim($input));

        if ($normalized === '') {
            throw TranslationException::fromInvalidBcp47(
                input: $input,
            );
        }

        $parts = \explode('-', $normalized);
        $language = \mb_strtolower($parts[0]);

        if (\preg_match('/^[a-z]{2,3}$/', $language) !== 1) {
            throw TranslationException::fromInvalidBcp47(
                input: $input,
            );
        }

        $script = null;
        $region = null;

        foreach (\array_slice($parts, 1) as $part) {
            if ($part === '') {
                throw TranslationException::fromInvalidBcp47(
                    input: $input,
                );
            }

            if ($script === null && \preg_match('/^[A-Za-z]{4}$/', $part) === 1) {
                $script = \mb_ucfirst(\mb_strtolower($part));

                continue;
            }

            if ($region === null && \preg_match('/^([A-Za-z]{2}|\d{3})$/', $part) === 1) {
                $region = \mb_strtoupper($part);

                continue;
            }

            throw TranslationException::fromInvalidBcp47(
                input: $input,
            );
        }

        return new self(
            language: $language,
            script: $script,
            region: $region,
        );
    }

    /**
     * @return iterable<int, self>
     */
    public function fallbackChain(): iterable
    {
        yield $this;

        if ($this->script !== null && $this->region !== null) {
            yield new self(
                language: $this->language,
                script: $this->script,
            );
        }

        if ($this->script !== null || $this->region !== null) {
            yield new self(
                language: $this->language,
            );
        }
    }

    /**
     * @throws TranslationException
     */
    private static function capitalize(
        string|false $name,
        string $tag,
    ): string {
        if ($name === false) {
            // @codeCoverageIgnoreStart
            throw TranslationException::fromDisplayNameFailure(
                tag: $tag,
            );
            // @codeCoverageIgnoreEnd
        }

        return \mb_ucfirst($name);
    }
}
