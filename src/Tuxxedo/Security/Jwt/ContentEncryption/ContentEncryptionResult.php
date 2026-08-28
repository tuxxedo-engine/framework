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

namespace Tuxxedo\Security\Jwt\ContentEncryption;

class ContentEncryptionResult
{
    public function __construct(
        public readonly string $ciphertext,
        public readonly string $initializationVector,
        public readonly string $authenticationTag,
    ) {
    }
}
