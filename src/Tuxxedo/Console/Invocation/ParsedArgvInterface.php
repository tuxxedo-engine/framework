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

namespace Tuxxedo\Console\Invocation;

interface ParsedArgvInterface
{
    /**
     * @var list<string>
     */
    public array $positionals {
        get;
    }

    /**
     * @var array<string, list<string>>
     */
    public array $options {
        get;
    }

    /**
     * @var array<string, bool>
     */
    public array $flags {
        get;
    }
}
