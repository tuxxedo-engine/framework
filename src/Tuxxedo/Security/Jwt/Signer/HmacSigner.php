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

namespace Tuxxedo\Security\Jwt\Signer;

use Tuxxedo\Security\Jwt\Algorithm;
use Tuxxedo\Security\Jwt\JwtException;
use Tuxxedo\Security\Jwt\Key\SymmetricKey;

class HmacSigner implements SignerInterface
{
    private readonly string $hashAlgorithm;

    /**
     * @throws JwtException
     */
    public function __construct(
        Algorithm $algorithm,
        private readonly SymmetricKey $key,
    ) {
        $this->hashAlgorithm = match ($algorithm) {
            Algorithm::HS256 => 'sha256',
            Algorithm::HS384 => 'sha384',
            Algorithm::HS512 => 'sha512',
            default => throw JwtException::fromUnexpectedAlgorithm(
                context: self::class,
                algorithm: $algorithm->name,
            ),
        };
    }

    public function sign(
        string $payload,
    ): string {
        return \hash_hmac(
            algo: $this->hashAlgorithm,
            data: $payload,
            key: $this->key->secret,
            binary: true,
        );
    }
}
