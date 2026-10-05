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

use Tuxxedo\View\Lumi\Library\Function\FunctionProviderInterface;
use Tuxxedo\View\Lumi\Library\LibraryProviderInterface;
use Tuxxedo\View\Lumi\Library\Standard\Filter\CollectionFilters;
use Tuxxedo\View\Lumi\Library\Standard\Filter\DateFilters;
use Tuxxedo\View\Lumi\Library\Standard\Filter\DebugFilters;
use Tuxxedo\View\Lumi\Library\Standard\Filter\EscapeFilters;
use Tuxxedo\View\Lumi\Library\Standard\Filter\JsonFilters;
use Tuxxedo\View\Lumi\Library\Standard\Filter\StringFilters;

class StandardLibrary implements LibraryProviderInterface
{
    public function functions(): FunctionProviderInterface
    {
        return new StandardFunctions();
    }

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
            JsonFilters::class,
            StringFilters::class,
        ];
    }
}
