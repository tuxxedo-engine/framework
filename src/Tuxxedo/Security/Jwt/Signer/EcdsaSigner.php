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
use Tuxxedo\Security\Jwt\EcdsaSignatureCodec;
use Tuxxedo\Security\Jwt\JwtException;
use Tuxxedo\Security\Jwt\Key\EcdsaPrivateKey;
use Tuxxedo\Security\Jwt\OpensslSignature;

class EcdsaSigner implements SignerInterface
{
    private readonly int $opensslAlgorithm;
    private readonly int $componentLength;
    private readonly string $algorithmIdentifier;
    private readonly EcdsaSignatureCodec $codec;

    /**
     * @throws JwtException
     */
    public function __construct(
        Algorithm $algorithm,
        private readonly EcdsaPrivateKey $key,
    ) {
        [$this->opensslAlgorithm, $this->componentLength] = match ($algorithm) {
            Algorithm::ES256 => [
                \OPENSSL_ALGO_SHA256,
                32,
            ],
            Algorithm::ES384 => [
                \OPENSSL_ALGO_SHA384,
                48,
            ],
            Algorithm::ES512 => [
                \OPENSSL_ALGO_SHA512,
                66,
            ],
            default => throw JwtException::fromUnexpectedAlgorithm(
                context: self::class,
                algorithm: $algorithm->identifier(),
            ),
        };

        $this->algorithmIdentifier = $algorithm->identifier();
        $this->codec = new EcdsaSignatureCodec(
            algorithmIdentifier: $this->algorithmIdentifier,
            componentLength: $this->componentLength,
        );
    }

    public function sign(
        string $payload,
    ): string {
        $der = OpensslSignature::sign(
            privateKey: $this->key->handle,
            opensslAlgorithm: $this->opensslAlgorithm,
            payload: $payload,
            algorithmIdentifier: $this->algorithmIdentifier,
        );

        return $this->codec->derToJose(
            der: $der,
        );
    }
}
