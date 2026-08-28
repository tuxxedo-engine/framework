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

readonly class JweToken implements JweTokenInterface
{
    public function __construct(
        public HeaderInterface $header,
        public ClaimsInterface $claims,
        public string $encryptedKey,
        public string $initializationVector,
        public string $ciphertext,
        public string $authenticationTag,
        public string $compact,
    ) {
    }
}
