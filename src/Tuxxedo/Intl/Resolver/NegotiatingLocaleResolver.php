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

namespace Tuxxedo\Intl\Resolver;

use Tuxxedo\Intl\Config\IntlConfigInterface;
use Tuxxedo\Intl\LocaleInterface;
use Tuxxedo\Intl\Source\LocaleSourceRegistryInterface;

class NegotiatingLocaleResolver implements LocaleResolverInterface
{
    public function __construct(
        private readonly IntlConfigInterface $config,
        private readonly LocaleSourceRegistryInterface $sources,
    ) {
    }

    public function currentLocale(): LocaleInterface
    {
        foreach ($this->sources->sortedSources() as $source) {
            $candidate = $source->detect();

            if ($candidate === null) {
                continue;
            }

            if ($this->isAllowed($candidate)) {
                return $candidate;
            }
        }

        return $this->config->defaultLocale;
    }

    private function isAllowed(
        LocaleInterface $locale,
    ): bool {
        if ($this->config->supportedLocales === []) {
            return true;
        }

        if ($locale->tag === $this->config->defaultLocale->tag) {
            return true;
        }

        foreach ($this->config->supportedLocales as $supported) {
            if ($supported->tag === $locale->tag) {
                return true;
            }
        }

        return false;
    }
}
