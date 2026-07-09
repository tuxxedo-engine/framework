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

class EcdsaPublicKey implements KeyInterface
{
    public readonly \OpenSSLAsymmetricKey $handle;

    /**
     * @throws JwtException
     */
    public function __construct(
        \OpenSSLAsymmetricKey|string $key,
        public readonly ?string $keyId = null,
    ) {
        if (\is_string($key)) {
            $parsed = \openssl_pkey_get_public($key);

            if ($parsed === false) {
                throw JwtException::fromInvalidPublicKey(
                    type: 'ECDSA',
                );
            }

            $key = $parsed;
        }

        $this->handle = $key;
    }
}
