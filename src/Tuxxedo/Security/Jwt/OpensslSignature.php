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

class OpensslSignature
{
    /**
     * @throws JwtException
     */
    public static function sign(
        \OpenSSLAsymmetricKey $privateKey,
        int $opensslAlgorithm,
        string $payload,
        string $algorithmIdentifier,
    ): string {
        $signature = null;
        $ok = \openssl_sign(
            data: $payload,
            signature: $signature,
            private_key: $privateKey,
            algorithm: $opensslAlgorithm,
        );

        if ($ok === false || !\is_string($signature)) {
            throw JwtException::fromSigningFailed(
                algorithm: $algorithmIdentifier,
            );
        }

        return $signature;
    }

    /**
     * @throws JwtException
     */
    public static function verify(
        \OpenSSLAsymmetricKey $publicKey,
        int $opensslAlgorithm,
        string $payload,
        string $signature,
        string $algorithmIdentifier,
    ): bool {
        $result = \openssl_verify(
            data: $payload,
            signature: $signature,
            public_key: $publicKey,
            algorithm: $opensslAlgorithm,
        );

        if ($result === -1) {
            throw JwtException::fromVerificationError(
                algorithm: $algorithmIdentifier,
            );
        }

        return $result === 1;
    }
}
