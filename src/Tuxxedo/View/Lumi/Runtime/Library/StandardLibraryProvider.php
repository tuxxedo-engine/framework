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

namespace Tuxxedo\View\Lumi\Runtime\Library;

use Tuxxedo\View\Lumi\Library\Filter\FilterProviderInterface;
use Tuxxedo\View\Lumi\Library\Function\FunctionProviderInterface;
use Tuxxedo\View\Lumi\Library\LibraryProviderInterface;

class StandardLibraryProvider implements LibraryProviderInterface
{
    public function filters(): FilterProviderInterface
    {
        return new StandardFilters();
    }

    public function functions(): FunctionProviderInterface
    {
        return new StandardFunctions();
    }
}
