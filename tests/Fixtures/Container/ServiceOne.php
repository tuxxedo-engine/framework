<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Fixtures\Container;

class ServiceOne implements ServiceOneInterface
{
    public function foo(): string
    {
        return 'bar';
    }
}
