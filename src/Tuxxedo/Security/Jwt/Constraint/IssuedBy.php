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

class IssuedBy implements ConstraintInterface
{
    /**
     * @var list<string>
     */
    private readonly array $issuers;

    public function __construct(
        string ...$issuers,
    ) {
        $this->issuers = \array_values($issuers);
    }

    public function check(
        TokenInterface $token,
    ): void {
        $issuer = $token->claims->issuer;

        if ($issuer === null) {
            throw JwtException::fromMissingClaim(
                claim: 'iss',
            );
        }

        if (!\in_array($issuer, $this->issuers, strict: true)) {
            throw JwtException::fromInvalidIssuer(
                actual: $issuer,
            );
        }
    }
}
