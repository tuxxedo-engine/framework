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

namespace Tuxxedo\Security\Jwt;

class JwtTokenAccessor implements JwtTokenAccessorInterface
{
    private ?TokenInterface $token = null;

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
