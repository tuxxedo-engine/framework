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

use Tuxxedo\Security\Jwt\ContentEncryptionAlgorithm;
use Tuxxedo\Security\Jwt\JwtException;

class ContentDecrypterFactory
{
    /**
     * @throws JwtException
     */
    public static function createFromAlgorithm(
        ContentEncryptionAlgorithm $algorithm,
    ): ContentDecrypterInterface {
        return match ($algorithm) { // @codeCoverageIgnore
            ContentEncryptionAlgorithm::A128GCM, ContentEncryptionAlgorithm::A192GCM, ContentEncryptionAlgorithm::A256GCM => new AesGcmContentDecrypter(
                algorithm: $algorithm,
            ),
            ContentEncryptionAlgorithm::A128CBC_HS256, ContentEncryptionAlgorithm::A192CBC_HS384, ContentEncryptionAlgorithm::A256CBC_HS512 => new AesCbcHmacContentDecrypter(
                algorithm: $algorithm,
            ),
        };
    }
}
