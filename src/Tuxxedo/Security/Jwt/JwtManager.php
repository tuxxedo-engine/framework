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

use Tuxxedo\Security\Crypto\Base64Url;
use Tuxxedo\Security\Crypto\CryptoException;
use Tuxxedo\Security\Jwt\Constraint\ConstraintInterface;
use Tuxxedo\Security\Jwt\Constraint\SignedWith;
use Tuxxedo\Security\Jwt\Key\KeyInterface;
use Tuxxedo\Security\Jwt\Signer\SignerFactory;

class JwtManager implements JwtManagerInterface
{
    public function encode(
        array $claims,
        Algorithm $algorithm,
        KeyInterface $key,
        array $extraHeader = [],
    ): TokenInterface {
        $headerData = [
            'typ' => 'JWT',
        ];

        if ($key->keyId !== null) {
            $headerData['kid'] = $key->keyId;
        }

        $headerData = \array_merge($headerData, $extraHeader);
        $headerData['alg'] = $algorithm->identifier();

        $encodedHeader = $this->base64UrlEncode(
            bytes: $this->jsonEncode($headerData),
        );

        $encodedClaims = $this->base64UrlEncode(
            bytes: $this->jsonEncode($claims),
        );

        $signingInput = $encodedHeader . '.' . $encodedClaims;

        $signer = SignerFactory::createFromAlgorithm(
            algorithm: $algorithm,
            key: $key,
        );

        $signature = $signer->sign(
            payload: $signingInput,
        );

        $encodedSignature = $this->base64UrlEncode(
            bytes: $signature,
        );

        $compact = $signingInput . '.' . $encodedSignature;

        return new Token(
            header: new Header($headerData),
            claims: new Claims($claims),
            signature: $signature,
            compact: $compact,
        );
    }

    public function parse(
        string $compact,
    ): TokenInterface {
        $segments = \explode('.', $compact);

        if (\sizeof($segments) !== 3) {
            throw JwtException::fromMalformedToken(
                token: $compact,
            );
        }

        [$encodedHeader, $encodedClaims, $encodedSignature] = $segments;

        $headerData = $this->jsonDecode(
            json: $this->base64UrlDecode($encodedHeader),
        );

        $claimsData = $this->jsonDecode(
            json: $this->base64UrlDecode($encodedClaims),
        );

        $signature = $this->base64UrlDecode(
            segment: $encodedSignature,
        );

        return new Token(
            header: new Header($headerData),
            claims: new Claims($claimsData),
            signature: $signature,
            compact: $compact,
        );
    }

    public function decode(
        string $compact,
        ConstraintInterface ...$constraints,
    ): TokenInterface {
        $hasSignedWith = false;

        foreach ($constraints as $constraint) {
            if ($constraint instanceof SignedWith) {
                $hasSignedWith = true;

                break;
            }
        }

        if (!$hasSignedWith) {
            throw JwtException::fromMissingSignatureConstraint();
        }

        $token = $this->parse(
            compact: $compact,
        );

        foreach ($constraints as $constraint) {
            $constraint->check(
                token: $token,
            );
        }

        return $token;
    }

    private function base64UrlEncode(
        string $bytes,
    ): string {
        return Base64Url::encode(
            bytes: $bytes,
        );
    }

    /**
     * @throws JwtException
     */
    private function base64UrlDecode(
        string $segment,
    ): string {
        try {
            return Base64Url::decode(
                segment: $segment,
            );
        } catch (CryptoException $exception) {
            throw JwtException::fromInvalidBase64Segment(
                segment: $segment,
                previous: $exception,
            );
        }
    }

    /**
     * @param array<string, mixed> $data
     *
     * @throws JwtException
     */
    private function jsonEncode(
        array $data,
    ): string {
        try {
            return \json_encode(
                value: $data,
                flags: \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE,
            );
        } catch (\JsonException $e) {
            throw JwtException::fromJsonEncodeFailure(
                previous: $e,
            );
        }
    }

    /**
     * @return array<string, mixed>
     *
     * @throws JwtException
     */
    private function jsonDecode(
        string $json,
    ): array {
        try {
            $decoded = \json_decode(
                json: $json,
                associative: true,
                flags: \JSON_THROW_ON_ERROR,
            );
        } catch (\JsonException $e) {
            throw JwtException::fromJsonDecodeFailure(
                previous: $e,
            );
        }

        if (!\is_array($decoded)) {
            throw JwtException::fromNonObjectJsonSegment();
        }

        if ($decoded !== [] && \array_is_list($decoded)) {
            throw JwtException::fromNonObjectJsonSegment();
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }
}
