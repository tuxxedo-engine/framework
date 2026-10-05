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

namespace Tuxxedo\View\Lumi\Library\Standard\Filter;

use Tuxxedo\View\Lumi\Library\Attribute\LumiFilter;

class DebugFilters
{
    #[LumiFilter('dump')]
    public function dump(
        mixed $value,
    ): string {
        \ob_start();
        \var_dump($value);

        return \rtrim(\ob_get_clean());
    }
}
