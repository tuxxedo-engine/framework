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
use Tuxxedo\View\Lumi\Library\Attribute\LumiFunction;
use Tuxxedo\View\Lumi\Runtime\RuntimeContextInterface;

class StubFunctionClass
{
    #[LumiFunction('stub_upper')]
    public function upper(
        string $value,
    ): string {
        return \strtoupper($value);
    }

    #[LumiFunction('stub_context', aliases: ['stub_ctx'])]
    public function withContext(
        #[Context]
        RuntimeContextInterface $context,
        string $value,
    ): string {
        return $value . ':ctx';
    }
}
