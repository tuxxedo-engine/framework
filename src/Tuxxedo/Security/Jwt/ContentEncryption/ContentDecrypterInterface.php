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

use Tuxxedo\Security\Jwt\JwtException;

interface ContentDecrypterInterface
{
    /**
     * @throws JwtException
     */
    public function decrypt(
        string $ciphertext,
        string $initializationVector,
        string $authenticationTag,
        #[\SensitiveParameter] string $contentEncryptionKey,
        string $additionalAuthenticatedData,
    ): string;
}
