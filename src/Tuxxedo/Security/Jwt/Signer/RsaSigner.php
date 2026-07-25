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

use Tuxxedo\Security\Crypto\CryptoException;
use Tuxxedo\Security\Crypto\Signature\OpensslSignature;
use Tuxxedo\Security\Jwt\Algorithm;
use Tuxxedo\Security\Jwt\JwtException;
use Tuxxedo\Security\Jwt\Key\RsaPrivateKey;

class RsaSigner implements SignerInterface
{
    private readonly int $opensslAlgorithm;
    private readonly string $algorithmIdentifier;

    /**
     * @throws JwtException
     */
    public function __construct(
        Algorithm $algorithm,
        private readonly RsaPrivateKey $key,
    ) {
        $this->opensslAlgorithm = match ($algorithm) {
            Algorithm::RS256 => \OPENSSL_ALGO_SHA256,
            Algorithm::RS384 => \OPENSSL_ALGO_SHA384,
            Algorithm::RS512 => \OPENSSL_ALGO_SHA512,
            default => throw JwtException::fromUnexpectedAlgorithm(
                context: self::class,
                algorithm: $algorithm->identifier(),
            ),
        };

        $this->algorithmIdentifier = $algorithm->identifier();
    }

    public function sign(
        string $payload,
    ): string {
        try {
            return OpensslSignature::sign(
                privateKey: $this->key->handle,
                opensslAlgorithm: $this->opensslAlgorithm,
                payload: $payload,
                algorithmIdentifier: $this->algorithmIdentifier,
            );
        } catch (CryptoException $exception) { // @codeCoverageIgnore
            // @codeCoverageIgnoreStart
            throw JwtException::fromSigningFailed(
                algorithm: $this->algorithmIdentifier,
                previous: $exception,
            );
            // @codeCoverageIgnoreEnd
        }
    }
}
