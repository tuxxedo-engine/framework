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

namespace Tuxxedo\Intl\Source;

use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Intl\Config\IntlConfigInterface;
use Tuxxedo\Intl\Locale;
use Tuxxedo\Intl\LocaleInterface;
use Tuxxedo\Intl\TranslationException;

class AcceptLanguageSource extends AbstractMemoizedLocaleSource
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly IntlConfigInterface $config,
    ) {
    }

    protected function resolve(): ?LocaleInterface
    {
        $supported = $this->supportedLocales();

        foreach ($this->request->headers->preferredLanguages as $candidate) {
            try {
                $locale = Locale::parse(
                    input: $candidate,
                );
            } catch (TranslationException) {
                continue;
            }

            if ($supported === []) {
                return $locale;
            }

            $match = self::match(
                locale: $locale,
                supported: $supported,
            );

            if ($match !== null) {
                return $match;
            }
        }

        return null;
    }

    /**
     * @return list<LocaleInterface>
     */
    private function supportedLocales(): array
    {
        if ($this->config->supportedLocales === []) {
            return [];
        }

        return [
            $this->config->defaultLocale,
            ...$this->config->supportedLocales,
        ];
    }

    /**
     * @param list<LocaleInterface> $supported
     */
    private static function match(
        LocaleInterface $locale,
        array $supported,
    ): ?LocaleInterface {
        foreach ($supported as $candidate) {
            if ($candidate->tag === $locale->tag) {
                return $candidate;
            }
        }

        foreach ($locale->fallbackChain() as $broader) {
            foreach ($supported as $candidate) {
                if ($candidate->tag === $broader->tag) {
                    return $candidate;
                }
            }
        }

        foreach ($supported as $candidate) {
            foreach ($candidate->fallbackChain() as $broader) {
                if ($broader->tag === $locale->tag) {
                    return $candidate;
                }
            }
        }

        return null;
    }
}
