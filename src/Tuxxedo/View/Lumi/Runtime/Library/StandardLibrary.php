<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\View\Lumi\Runtime\Library;

use Tuxxedo\View\Lumi\Library\Filter\FilterProviderInterface;
use Tuxxedo\View\Lumi\Library\Function\FunctionProviderInterface;
use Tuxxedo\View\Lumi\Library\LibraryInterface;

class StandardLibrary implements LibraryInterface
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
