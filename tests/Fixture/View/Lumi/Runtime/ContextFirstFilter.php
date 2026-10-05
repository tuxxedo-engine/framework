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

namespace Fixture\View\Lumi\Runtime;

use Tuxxedo\View\Lumi\Library\Attribute\Context;
use Tuxxedo\View\Lumi\Library\Attribute\LumiFilter;
use Tuxxedo\View\Lumi\Runtime\RuntimeContextInterface;

class ContextFirstFilter
{
    public ?RuntimeContextInterface $lastContext = null;
    public mixed $lastValue = null;

    #[LumiFilter('context_first')]
    public function run(
        #[Context] RuntimeContextInterface $context,
        mixed $value,
    ): mixed {
        $this->lastContext = $context;
        $this->lastValue = $value;

        return $value;
    }
}
