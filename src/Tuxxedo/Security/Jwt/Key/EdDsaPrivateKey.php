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

namespace Tuxxedo\Security\Jwt\Key;

use Tuxxedo\Security\Jwt\JwtException;

class EdDsaPrivateKey implements KeyInterface
{
    /**
     * @param non-empty-string $bytes
     *
     * @throws JwtException
     */
    public function __construct(
        #[\SensitiveParameter] public readonly string $bytes,
        public readonly ?string $keyId = null,
    ) {
        if (\strlen($this->bytes) !== \SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
            throw JwtException::fromInvalidPrivateKey(
                type: 'EdDSA',
            );
        }
    }

    /**
     * @throws JwtException
     */
    public function toPublic(): EdDsaPublicKey
    {
        /** @var non-empty-string $publicBytes */
        $publicBytes = \sodium_crypto_sign_publickey_from_secretkey($this->bytes);

        return new EdDsaPublicKey(
            bytes: $publicBytes,
            keyId: $this->keyId,
        );
    }
}
