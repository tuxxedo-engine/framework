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

namespace Tuxxedo\Console\Middleware;

use Tuxxedo\Console\Descriptor\CommandDescriptorInterface;

interface CommandInvocationInterface
{
    public CommandDescriptorInterface $descriptor {
        get;
    }

    /**
     * @var list<mixed>
     */
    public array $arguments {
        get;
    }
}
