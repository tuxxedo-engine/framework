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

namespace Support\Security\Jwt;

use Tuxxedo\Security\Jwt\JwsTokenInterface;
use Tuxxedo\Security\Jwt\JwtTokenAccessorInterface;

class StubJwtTokenAccessor implements JwtTokenAccessorInterface
{
    public function __construct(
        private ?JwsTokenInterface $token = null,
    ) {
    }

    public function current(): ?JwsTokenInterface
    {
        return $this->token;
    }

    public function setCurrent(
        ?JwsTokenInterface $token,
    ): void {
        $this->token = $token;
    }
}
