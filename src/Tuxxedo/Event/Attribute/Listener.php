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

namespace Tuxxedo\Event\Attribute;

use Tuxxedo\Event\ListenerPriority;

#[\Attribute(flags: \Attribute::TARGET_METHOD)]
readonly class Listener
{
    public function __construct(
        public ?ListenerPriority $priority = null,
    ) {
    }
}
