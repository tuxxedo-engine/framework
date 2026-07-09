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

class HasClaim implements ConstraintInterface
{
    /**
     * @param \Closure(mixed): bool $predicate
     */
    public function __construct(
        private readonly string $claim,
        private readonly \Closure $predicate,
    ) {
    }

    public function check(
        TokenInterface $token,
    ): void {
        if (!$token->claims->has($this->claim)) {
            throw JwtException::fromMissingClaim(
                claim: $this->claim,
            );
        }

        if (!($this->predicate)($token->claims->get($this->claim))) {
            throw JwtException::fromClaimPredicateFailed(
                claim: $this->claim,
            );
        }
    }
}
