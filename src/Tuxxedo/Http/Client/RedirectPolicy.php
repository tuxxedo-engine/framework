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

namespace Tuxxedo\Http\Client;

readonly class RedirectPolicy implements RedirectPolicyInterface
{
    public function __construct(
        public int $max = 5,
        public bool $followCrossOrigin = true,
        public bool $keepCredentialsAcrossOrigins = false,
    ) {
    }
}
