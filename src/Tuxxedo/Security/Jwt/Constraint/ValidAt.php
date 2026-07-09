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

namespace Tuxxedo\Security\Jwt\Constraint;

use Tuxxedo\Security\Jwt\JwtException;
use Tuxxedo\Security\Jwt\TokenInterface;
use Tuxxedo\Temporal\ClockInterface;

class ValidAt implements ConstraintInterface
{
    public function __construct(
        private readonly ClockInterface $clock,
        private readonly int $leewaySeconds = 0,
    ) {
    }

    public function check(
        TokenInterface $token,
    ): void {
        $now = $this->clock->now()->getTimestamp();

        $expiresAt = $token->claims->expiresAt;

        if ($expiresAt !== null && $now > $expiresAt->getTimestamp() + $this->leewaySeconds) {
            throw JwtException::fromExpiredToken();
        }

        $notBefore = $token->claims->notBefore;

        if ($notBefore !== null && $now + $this->leewaySeconds < $notBefore->getTimestamp()) {
            throw JwtException::fromTokenNotYetValid();
        }
    }
}
