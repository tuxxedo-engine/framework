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

namespace Tuxxedo\View\Lumi\Runtime\Introspector;

interface CallableParameterInterface
{
    public string $name {
        get;
    }

    public int $position {
        get;
    }

    public bool $optional {
        get;
    }

    public bool $variadic {
        get;
    }
}
