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

namespace Tuxxedo\Security\Jwt\Verifier;

use Tuxxedo\Security\Jwt\Algorithm;
use Tuxxedo\Security\Jwt\JwtException;
use Tuxxedo\Security\Jwt\Key\RsaPrivateKey;
use Tuxxedo\Security\Jwt\Key\RsaPublicKey;
use Tuxxedo\Security\Jwt\OpensslSignature;

// @todo Narrow union to public-only or derive public from private at construction; openssl_verify silently rejects private-key handles on some OpenSSL builds
class RsaVerifier implements VerifierInterface
{
    private readonly int $opensslAlgorithm;
    private readonly string $algorithmIdentifier;

    /**
     * @throws JwtException
     */
    public function __construct(
        Algorithm $algorithm,
        private readonly RsaPublicKey|RsaPrivateKey $key,
    ) {
        $this->opensslAlgorithm = match ($algorithm) {
            Algorithm::RS256 => \OPENSSL_ALGO_SHA256,
            Algorithm::RS384 => \OPENSSL_ALGO_SHA384,
            Algorithm::RS512 => \OPENSSL_ALGO_SHA512,
            default => throw JwtException::fromUnexpectedAlgorithm(
                context: self::class,
                algorithm: $algorithm->name,
            ),
        };

        $this->algorithmIdentifier = $algorithm->name;
    }

    public function verify(
        string $payload,
        string $signature,
    ): bool {
        return OpensslSignature::verify(
            publicKey: $this->key->handle,
            opensslAlgorithm: $this->opensslAlgorithm,
            payload: $payload,
            signature: $signature,
            algorithmIdentifier: $this->algorithmIdentifier,
        );
    }
}
