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

class JwtException extends \Exception
{
    public static function fromMalformedToken(
        string $token,
    ): self {
        return new self(
            message: \sprintf(
                'Malformed JWT: expected 3 dot-separated segments, got "%s"',
                $token,
            ),
        );
    }

    public static function fromInvalidBase64Segment(
        string $segment,
    ): self {
        return new self(
            message: \sprintf(
                'JWT segment "%s" is not valid base64url encoded data',
                $segment,
            ),
        );
    }

    public static function fromJsonEncodeFailure(
        \JsonException $previous,
    ): self {
        return new self(
            message: 'JWT segment JSON encoding failed',
            previous: $previous,
        );
    }

    public static function fromJsonDecodeFailure(
        \JsonException $previous,
    ): self {
        return new self(
            message: 'JWT segment JSON decoding failed',
            previous: $previous,
        );
    }

    public static function fromNonObjectJsonSegment(): self
    {
        return new self(
            message: 'JWT segment did not decode to a JSON object',
        );
    }

    public static function fromMissingHeader(
        string $header,
    ): self {
        return new self(
            message: \sprintf(
                'JWT is missing required header "%s"',
                $header,
            ),
        );
    }

    public static function fromInvalidHeaderValue(
        string $header,
    ): self {
        return new self(
            message: \sprintf(
                'JWT header "%s" has an invalid value',
                $header,
            ),
        );
    }

    public static function fromInvalidPublicKey(
        string $type,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid %s public key: could not be parsed',
                $type,
            ),
        );
    }

    public static function fromInvalidPrivateKey(
        string $type,
    ): self {
        return new self(
            message: \sprintf(
                'Invalid %s private key: could not be parsed',
                $type,
            ),
        );
    }

    /**
     * @codeCoverageIgnore
     */
    public static function fromPublicKeyDerivationFailed(
        string $type,
    ): self {
        return new self(
            message: \sprintf(
                'Failed to derive %s public key from private key',
                $type,
            ),
        );
    }

    public static function fromIncompatibleKey(
        string $algorithm,
        string $expected,
        string $given,
    ): self {
        return new self(
            message: \sprintf(
                'JWT algorithm "%s" requires a key of type "%s", got "%s"',
                $algorithm,
                $expected,
                $given,
            ),
        );
    }

    public static function fromUnexpectedAlgorithm(
        string $context,
        string $algorithm,
    ): self {
        return new self(
            message: \sprintf(
                'Unexpected JWT algorithm "%s" in "%s"',
                $algorithm,
                $context,
            ),
        );
    }

    // @codeCoverageIgnoreStart
    public static function fromSigningFailed(
        string $algorithm,
    ): self {
        return new self(
            message: \sprintf(
                'JWT signing with "%s" failed',
                $algorithm,
            ),
        );
    }

    public static function fromVerificationError(
        string $algorithm,
    ): self {
        return new self(
            message: \sprintf(
                'JWT signature verification with "%s" encountered an error',
                $algorithm,
            ),
        );
    }
    // @codeCoverageIgnoreEnd

    public static function fromInvalidSignatureLength(
        string $algorithm,
        int $expected,
        int $given,
    ): self {
        return new self(
            message: \sprintf(
                'JWT signature for "%s" has invalid length: expected %d bytes, got %d',
                $algorithm,
                $expected,
                $given,
            ),
        );
    }

    public static function fromInvalidSignature(
        string $algorithm,
    ): self {
        return new self(
            message: \sprintf(
                'JWT signature for "%s" is malformed',
                $algorithm,
            ),
        );
    }

    public static function fromExpiredToken(): self
    {
        return new self(
            message: 'JWT has expired',
        );
    }

    public static function fromTokenNotYetValid(): self
    {
        return new self(
            message: 'JWT is not yet valid',
        );
    }

    public static function fromInvalidIssuer(
        string $actual,
    ): self {
        return new self(
            message: \sprintf(
                'JWT issuer "%s" is not permitted',
                $actual,
            ),
        );
    }

    public static function fromInvalidAudience(
        string $expected,
    ): self {
        return new self(
            message: \sprintf(
                'JWT audience does not include "%s"',
                $expected,
            ),
        );
    }

    public static function fromInvalidTokenId(
        string $expected,
        string $actual,
    ): self {
        return new self(
            message: \sprintf(
                'JWT identifier mismatch: expected "%s", got "%s"',
                $expected,
                $actual,
            ),
        );
    }

    public static function fromMissingClaim(
        string $claim,
    ): self {
        return new self(
            message: \sprintf(
                'JWT is missing required claim "%s"',
                $claim,
            ),
        );
    }

    public static function fromSignatureMismatch(): self
    {
        return new self(
            message: 'JWT signature does not match the expected value',
        );
    }

    public static function fromAlgorithmMismatch(
        string $expected,
        string $given,
    ): self {
        return new self(
            message: \sprintf(
                'JWT algorithm mismatch: expected "%s", got "%s"',
                $expected,
                $given,
            ),
        );
    }

    public static function fromClaimPredicateFailed(
        string $claim,
    ): self {
        return new self(
            message: \sprintf(
                'JWT claim "%s" did not satisfy the required predicate',
                $claim,
            ),
        );
    }

    public static function fromNoMatchingKey(
        string $keyId,
    ): self {
        return new self(
            message: \sprintf(
                'JWT key set has no key matching kid "%s"',
                $keyId,
            ),
        );
    }

    public static function fromMissingSignatureConstraint(): self
    {
        return new self(
            message: 'JWT decode requires at least one SignedWith constraint; use parse() for unverified access',
        );
    }

    public static function fromMissingToken(): self
    {
        return new self(
            message: 'No JWT is available on the current request',
        );
    }
}
