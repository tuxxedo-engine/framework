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

namespace Tuxxedo\Temporal;

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;

// @todo Consider extending this namespace with more functionality
// @todo As functionality improves here, make sure that Lumi's standard library supports this
#[DefaultImplementation(class: SystemClock::class, lifecycle: Lifecycle::SINGLETON)]
interface ClockInterface
{
    public function now(): \DateTimeImmutable;
}
