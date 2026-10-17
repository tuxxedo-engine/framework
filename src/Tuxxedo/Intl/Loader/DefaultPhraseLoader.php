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

use Tuxxedo\Intl\Config\IntlConfigInterface;
use Tuxxedo\Intl\LocaleInterface;

class DefaultPhraseLoader implements PhraseLoaderInterface
{
    private readonly PhraseLoaderInterface $loader;

    public function __construct(
        IntlConfigInterface $config,
    ) {
        $engine = new FlatFileLoader(
            rootDirectory: \dirname(__DIR__, 4) . '/resources/intl',
        );

        $this->loader = $config->rootDirectory !== null
            ? new MergingLoader(
                new FlatFileLoader(
                    rootDirectory: $config->rootDirectory,
                ),
                $engine,
            )
            : $engine;
    }

    public function load(
        LocaleInterface $locale,
        string $group,
    ): ?array {
        return $this->loader->load(
            locale: $locale,
            group: $group,
        );
    }
}
