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

namespace Fixture\View\Lumi\Runtime\Introspector;

use Tuxxedo\View\Lumi\Library\Attribute\Context;
use Tuxxedo\View\Lumi\Library\Attribute\LumiFilter;
use Tuxxedo\View\Lumi\Library\Attribute\LumiFunction;
use Tuxxedo\View\Lumi\Runtime\RuntimeContextInterface;

class StubNamedArgumentClass
{
    #[LumiFunction('stub_named')]
    #[LumiFilter('stub_named')]
    public function named(
        string $value,
        string $prefix = '<',
        string $suffix = '>',
        #[Context]
        ?RuntimeContextInterface $context = null,
    ): string {
        return $prefix . $value . $suffix . (
            $context !== null
                ? ':ctx'
                : ''
        );
    }
}
