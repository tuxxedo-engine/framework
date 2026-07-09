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

class IdentifiedBy implements ConstraintInterface
{
    public function __construct(
        private readonly string $id,
    ) {
    }

    public function check(
        TokenInterface $token,
    ): void {
        $id = $token->claims->id;

        if ($id === null) {
            throw JwtException::fromMissingClaim(
                claim: 'jti',
            );
        }

        if ($id !== $this->id) {
            throw JwtException::fromInvalidTokenId(
                expected: $this->id,
                actual: $id,
            );
        }
    }
}
