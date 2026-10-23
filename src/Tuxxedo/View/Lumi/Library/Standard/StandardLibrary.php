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

namespace Tuxxedo\View\Lumi\Library\Standard;

use Tuxxedo\View\Lumi\LumiConfiguratorInterface;

class StandardLibrary
{
    /**
     * @return list<class-string>
     */
    public static function classes(): array
    {
        return [
            CollectionLibrary::class,
            ConfigLibrary::class,
            CsrfLibrary::class,
            DateLibrary::class,
            DebugLibrary::class,
            EscapeLibrary::class,
            IntlLibrary::class,
            JsonLibrary::class,
            MailLibrary::class,
            MathLibrary::class,
            RouteLibrary::class,
            StringLibrary::class,
        ];
    }

    public static function registerPhpFunctions(
        LumiConfiguratorInterface $configurator,
    ): void {
        $configurator->addFunction(
            name: 'count',
            aliases: [
                'sizeof',
            ],
        );

        $configurator->addFunction(
            name: 'constant',
            aliases: [
                'const',
            ],
        );

        $configurator->addFunction(
            name: 'base64',
            mappedName: 'base64_encode',
        );

        $configurator->addFunction(
            name: 'abs',
        );

        $configurator->addFunction(
            name: 'ceil',
        );

        $configurator->addFunction(
            name: 'floor',
        );

        $configurator->addFunction(
            name: 'max',
        );

        $configurator->addFunction(
            name: 'min',
        );

        $configurator->addFunction(
            name: 'random',
            mappedName: 'random_int',
        );

        $configurator->addFunction(
            name: 'unique',
            mappedName: 'array_unique',
        );

        $configurator->addFunction(
            name: 'keys',
            mappedName: 'array_keys',
        );

        $configurator->addFunction(
            name: 'values',
            mappedName: 'array_values',
        );
    }

    public static function registerPhpFilters(
        LumiConfiguratorInterface $configurator,
    ): void {
        $configurator->addFilter(
            name: 'count',
            aliases: [
                'sizeof',
            ],
        );

        $configurator->addFilter(
            name: 'abs',
        );

        $configurator->addFilter(
            name: 'ceil',
        );

        $configurator->addFilter(
            name: 'floor',
        );

        $configurator->addFilter(
            name: 'max',
        );

        $configurator->addFilter(
            name: 'min',
        );

        $configurator->addFilter(
            name: 'unique',
            mappedName: 'array_unique',
        );

        $configurator->addFilter(
            name: 'keys',
            mappedName: 'array_keys',
        );

        $configurator->addFilter(
            name: 'values',
            mappedName: 'array_values',
        );
    }
}
