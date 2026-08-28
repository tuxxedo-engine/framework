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

namespace Tuxxedo\Security\Crypto;

class CryptoException extends \Exception
{
    /**
     * @codeCoverageIgnore
     */
    public static function fromSigningFailed(
        string $algorithm,
    ): self {
        return new self(
            message: \sprintf(
                'Cryptographic signing with "%s" failed',
                $algorithm,
            ),
        );
    }

    /**
     * @codeCoverageIgnore
     */
    public static function fromVerificationError(
        string $algorithm,
    ): self {
        return new self(
            message: \sprintf(
                'Cryptographic signature verification with "%s" encountered an error',
                $algorithm,
            ),
        );
    }

    public static function fromInvalidBase64(
        string $segment,
    ): self {
        return new self(
            message: \sprintf(
                'Value "%s" is not valid base64url encoded data',
                $segment,
            ),
        );
    }

    public static function fromDerLengthOverflow(
        int $length,
    ): self {
        return new self(
            message: \sprintf(
                'ASN.1 DER length %d exceeds the 4-byte encoding limit',
                $length,
            ),
        );
    }

    public static function fromInvalidDerContextTag(
        int $tag,
    ): self {
        return new self(
            message: \sprintf(
                'ASN.1 DER context tag %d is outside the supported single-byte range (0-30)',
                $tag,
            ),
        );
    }

    public static function fromInvalidObjectIdentifier(
        string $oid,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid ASN.1 object identifier: "%s"',
                $oid,
            ),
        );
    }

    public static function fromMalformedEcdsaSignature(
        string $algorithmIdentifier,
    ): self {
        return new self(
            message: \sprintf(
                'Malformed ECDSA signature bytes for algorithm "%s"',
                $algorithmIdentifier,
            ),
        );
    }

    public static function fromInvalidSymmetricKeyLength(
        string $algorithm,
        string $expectedBytes,
        int $actualBytes,
    ): self {
        return new self(
            message: \sprintf(
                'Algorithm "%s" requires a symmetric key of %s bytes, got %d',
                $algorithm,
                $expectedBytes,
                $actualBytes,
            ),
        );
    }

    public static function fromInvalidCekLength(
        int $bytes,
    ): self {
        return new self(
            message: \sprintf(
                'AES Key Wrap requires a CEK of at least 16 bytes in multiples of 8, got %d',
                $bytes,
            ),
        );
    }

    public static function fromInvalidWrappedKeyLength(
        int $bytes,
    ): self {
        return new self(
            message: \sprintf(
                'AES Key Wrap requires a wrapped key of at least 24 bytes in multiples of 8, got %d',
                $bytes,
            ),
        );
    }

    public static function fromKeyUnwrapIntegrityFailed(): self
    {
        return new self(
            message: 'AES Key Wrap integrity check failed during unwrap',
        );
    }

    /**
     * @codeCoverageIgnore
     */
    public static function fromEncryptionFailure(
        string $context,
        ?\Throwable $previous = null,
    ): self {
        return new self(
            message: \sprintf(
                'Encryption in "%s" failed',
                $context,
            ),
            previous: $previous,
        );
    }
}
