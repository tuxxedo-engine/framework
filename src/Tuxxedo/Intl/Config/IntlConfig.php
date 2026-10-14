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

namespace Tuxxedo\Intl\Config;

use Tuxxedo\Config\Attribute\ConfigNamespace;
use Tuxxedo\Container\DefaultInitializer;
use Tuxxedo\Intl\Locale;
use Tuxxedo\Intl\MissingKeyMode;

#[ConfigNamespace('intl')]
#[DefaultInitializer(
    static function (): IntlConfigInterface {
        return new IntlConfig(
            defaultLocale: Locale::parse('en-US'),
        );
    },
)]
readonly class IntlConfig implements IntlConfigInterface
{
    /**
     * @param list<Locale> $supportedLocales
     */
    public function __construct(
        public Locale $defaultLocale,
        public ?string $rootDirectory = null,
        public array $supportedLocales = [],
        public MissingKeyMode $missingKeyMode = MissingKeyMode::PASSTHROUGH,
    ) {
    }
}
