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

namespace Tuxxedo\View\Lumi\Library;

use Tuxxedo\View\Lumi\Library\Filter\FilterProviderInterface;
use Tuxxedo\View\Lumi\Library\Function\FunctionProviderInterface;

interface LibraryInterface
{
    public function filters(): ?FilterProviderInterface;

    public function functions(): ?FunctionProviderInterface;
}
