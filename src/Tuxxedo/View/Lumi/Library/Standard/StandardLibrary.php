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

use Tuxxedo\View\Lumi\Library\Standard\Filter\CollectionFilters;
use Tuxxedo\View\Lumi\Library\Standard\Filter\DateFilters;
use Tuxxedo\View\Lumi\Library\Standard\Filter\DebugFilters;
use Tuxxedo\View\Lumi\Library\Standard\Filter\EscapeFilters;
use Tuxxedo\View\Lumi\Library\Standard\Filter\IntlFilters;
use Tuxxedo\View\Lumi\Library\Standard\Filter\JsonFilters;
use Tuxxedo\View\Lumi\Library\Standard\Filter\StringFilters;
use Tuxxedo\View\Lumi\Library\Standard\Function\ArrayFunctions;
use Tuxxedo\View\Lumi\Library\Standard\Function\ConfigFunctions;
use Tuxxedo\View\Lumi\Library\Standard\Function\CsrfFunctions;
use Tuxxedo\View\Lumi\Library\Standard\Function\DateFunctions;
use Tuxxedo\View\Lumi\Library\Standard\Function\DebugFunctions;
use Tuxxedo\View\Lumi\Library\Standard\Function\IntlFunctions;
use Tuxxedo\View\Lumi\Library\Standard\Function\JsonFunctions;
use Tuxxedo\View\Lumi\Library\Standard\Function\MailFunctions;
use Tuxxedo\View\Lumi\Library\Standard\Function\MathFunctions;
use Tuxxedo\View\Lumi\Library\Standard\Function\RouteFunctions;
use Tuxxedo\View\Lumi\Library\Standard\Function\StringFunctions;
use Tuxxedo\View\Lumi\LumiConfiguratorInterface;

class StandardLibrary
{
    /**
     * @return list<class-string>
     */
    public static function filters(): array
    {
        return [
            CollectionFilters::class,
            DateFilters::class,
            DebugFilters::class,
            EscapeFilters::class,
            IntlFilters::class,
            JsonFilters::class,
            StringFilters::class,
        ];
    }

    /**
     * @return list<class-string>
     */
    public static function functions(): array
    {
        return [
            ArrayFunctions::class,
            ConfigFunctions::class,
            CsrfFunctions::class,
            DateFunctions::class,
            DebugFunctions::class,
            IntlFunctions::class,
            JsonFunctions::class,
            MailFunctions::class,
            MathFunctions::class,
            RouteFunctions::class,
            StringFunctions::class,
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
}
