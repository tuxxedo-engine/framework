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

namespace Tuxxedo\Http\Client\Auth;

class MemoryTokenStore implements TokenStoreInterface
{
    public function __construct(
        private ?AccessTokenInterface $token = null,
    ) {
    }

    public function get(): ?AccessTokenInterface
    {
        return $this->token;
    }

    public function store(
        AccessTokenInterface $token,
    ): void {
        $this->token = $token;
    }

    public function clear(): void
    {
        $this->token = null;
    }
}
