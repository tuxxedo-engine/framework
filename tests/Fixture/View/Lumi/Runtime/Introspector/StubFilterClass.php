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

class StubFilterClass
{
    #[LumiFilter('stub_upper')]
    public function upper(
        string $value,
    ): string {
        return \strtoupper($value);
    }

    #[LumiFilter('stub_context', aliases: ['stub_ctx'])]
    public function withContext(
        string $value,
        #[Context] RuntimeContextInterface $context,
    ): string {
        return $value . ':ctx';
    }
}
