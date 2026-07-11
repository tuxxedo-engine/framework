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

use Tuxxedo\Security\Jwt\Key\EdDsaPublicKey;

class EdDsaVerifier implements VerifierInterface
{
    public function __construct(
        private readonly EdDsaPublicKey $key,
    ) {
    }

    public function verify(
        string $payload,
        string $signature,
    ): bool {
        if ($signature === '') {
            return false;
        }

        return \sodium_crypto_sign_verify_detached(
            signature: $signature,
            message: $payload,
            public_key: $this->key->bytes,
        );
    }
}
