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

namespace Tuxxedo\View\Lumi\Token;

interface TokenInterface
{
    public string $type {
        get;
    }

    public ?string $op1 {
        get;
    }

    public ?string $op2 {
        get;
    }
}
