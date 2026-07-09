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

class PermittedFor implements ConstraintInterface
{
    public function __construct(
        private readonly string $audience,
    ) {
    }

    public function check(
        TokenInterface $token,
    ): void {
        $audience = $token->claims->audience;

        if ($audience === null) {
            throw JwtException::fromMissingClaim(
                claim: 'aud',
            );
        }

        if (!\in_array($this->audience, $audience, strict: true)) {
            throw JwtException::fromInvalidAudience(
                expected: $this->audience,
            );
        }
    }
}
