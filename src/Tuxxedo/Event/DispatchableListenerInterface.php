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

namespace Tuxxedo\Event;

interface DispatchableListenerInterface
{
    /**
     * @var \Closure(): void
     */
    public \Closure $callback {
        get;
    }

    public string $eventName {
        get;
    }
}
