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

class StubArityClass
{
    #[LumiFunction('stub_wrap')]
    #[LumiFilter('stub_wrap')]
    public function wrap(
        #[Context]
        RuntimeContextInterface $context,
        string $value,
        string $prefix,
        string $suffix = '',
    ): string {
        return $prefix . $value . $suffix;
    }

    #[LumiFilter('stub_join')]
    public function join(
        string ...$values,
    ): string {
        return \join(',', $values);
    }
}
