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

use Tuxxedo\Security\Jwt\JwtTokenAccessorInterface;
use Tuxxedo\Security\Jwt\TokenInterface;

class StubJwtTokenAccessor implements JwtTokenAccessorInterface
{
    public function __construct(
        private ?TokenInterface $token = null,
    ) {
    }

    public function current(): ?TokenInterface
    {
        return $this->token;
    }

    public function setCurrent(
        ?TokenInterface $token,
    ): void {
        $this->token = $token;
    }
}
