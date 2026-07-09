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

use Tuxxedo\Security\Jwt\Key\EdDsaPrivateKey;

class EdDsaSigner implements SignerInterface
{
    public function __construct(
        private readonly EdDsaPrivateKey $key,
    ) {
    }

    public function sign(
        string $payload,
    ): string {
        return \sodium_crypto_sign_detached(
            message: $payload,
            secret_key: $this->key->bytes,
        );
    }
}
