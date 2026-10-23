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
use Tuxxedo\View\Lumi\Runtime\RuntimeContextInterface;

class StubContextOnlyFilterClass
{
    #[LumiFilter('stub_context_only')]
    public function contextOnly(
        #[Context]
        RuntimeContextInterface $context,
    ): string {
        return '';
    }
}
