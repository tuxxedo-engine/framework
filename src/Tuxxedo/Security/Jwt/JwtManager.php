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
use Tuxxedo\Security\Jwt\Constraint\EncryptedWith;
use Tuxxedo\Security\Jwt\Constraint\SignedWith;
use Tuxxedo\Security\Jwt\ContentEncryption\ContentDecrypterFactory;
use Tuxxedo\Security\Jwt\ContentEncryption\ContentEncrypterFactory;
use Tuxxedo\Security\Jwt\Decrypter\DecrypterFactory;
use Tuxxedo\Security\Jwt\Encrypter\EncrypterFactory;
use Tuxxedo\Security\Jwt\Key\KeyInterface;
use Tuxxedo\Security\Jwt\Key\SymmetricKey;
use Tuxxedo\Security\Jwt\Signer\SignerFactory;

class JwtManager implements JwtManagerInterface
{
    public function encode(
        array $claims,
        Algorithm $algorithm,
        KeyInterface $key,
        array $extraHeader = [],
    ): JwsTokenInterface {
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
    ): JwsTokenInterface {
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
    ): JwsTokenInterface {
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

    public function encrypt(
        array $claims,
        KeyManagementAlgorithm $keyAlgorithm,
        ContentEncryptionAlgorithm $contentAlgorithm,
        KeyInterface $key,
        array $extraHeader = [],
    ): JweTokenInterface {
        $encrypted = $this->performEncryption(
            plaintext: $this->jsonEncode($claims),
            keyAlgorithm: $keyAlgorithm,
            contentAlgorithm: $contentAlgorithm,
            key: $key,
            extraHeader: $extraHeader,
        );

        return new JweToken(
            header: new Header($encrypted['headerData']),
            claims: new Claims($claims),
            encryptedKey: $encrypted['encryptedKey'],
            initializationVector: $encrypted['initializationVector'],
            ciphertext: $encrypted['ciphertext'],
            authenticationTag: $encrypted['authenticationTag'],
            compact: $encrypted['compact'],
        );
    }

    public function encodeAndEncrypt(
        array $claims,
        Algorithm $signingAlgorithm,
        KeyInterface $signingKey,
        KeyManagementAlgorithm $keyAlgorithm,
        ContentEncryptionAlgorithm $contentAlgorithm,
        KeyInterface $encryptionKey,
        array $extraHeader = [],
    ): JweTokenInterface {
        $inner = $this->encode(
            claims: $claims,
            algorithm: $signingAlgorithm,
            key: $signingKey,
        );

        $outerHeader = $extraHeader;
        $outerHeader['cty'] = 'JWT';

        $encrypted = $this->performEncryption(
            plaintext: $inner->compact,
            keyAlgorithm: $keyAlgorithm,
            contentAlgorithm: $contentAlgorithm,
            key: $encryptionKey,
            extraHeader: $outerHeader,
        );

        return new JweToken(
            header: new Header($encrypted['headerData']),
            claims: new Claims($claims),
            encryptedKey: $encrypted['encryptedKey'],
            initializationVector: $encrypted['initializationVector'],
            ciphertext: $encrypted['ciphertext'],
            authenticationTag: $encrypted['authenticationTag'],
            compact: $encrypted['compact'],
        );
    }

    /**
     * @param array<string, mixed> $extraHeader
     * @return array{headerData: array<string, mixed>, encryptedKey: string, initializationVector: string, ciphertext: string, authenticationTag: string, compact: string}
     *
     * @throws JwtException
     */
    private function performEncryption(
        string $plaintext,
        KeyManagementAlgorithm $keyAlgorithm,
        ContentEncryptionAlgorithm $contentAlgorithm,
        KeyInterface $key,
        array $extraHeader,
    ): array {
        $headerData = [];

        if ($key->keyId !== null) {
            $headerData['kid'] = $key->keyId;
        }

        $headerData = \array_merge($headerData, $extraHeader);
        $headerData['alg'] = $keyAlgorithm->identifier();
        $headerData['enc'] = $contentAlgorithm->identifier();

        $encodedHeader = $this->base64UrlEncode(
            bytes: $this->jsonEncode($headerData),
        );

        if ($keyAlgorithm === KeyManagementAlgorithm::DIR) {
            if (!$key instanceof SymmetricKey) {
                throw JwtException::fromIncompatibleKey(
                    algorithm: $keyAlgorithm->identifier(),
                    expected: SymmetricKey::class,
                    given: $key::class,
                );
            }

            $expectedCekLength = $contentAlgorithm->keyLengthBytes();
            $actualCekLength = \strlen($key->secret);

            if ($actualCekLength !== $expectedCekLength) {
                throw JwtException::fromInvalidSymmetricKeyLength(
                    algorithm: $keyAlgorithm->identifier() . '+' . $contentAlgorithm->identifier(),
                    expectedBytes: \strval($expectedCekLength),
                    actualBytes: $actualCekLength,
                );
            }

            $cek = $key->secret;
            $encryptedKey = '';
        } else {
            $cek = \random_bytes($contentAlgorithm->keyLengthBytes());
            $encrypter = EncrypterFactory::createFromAlgorithm(
                algorithm: $keyAlgorithm,
                key: $key,
            );
            $encryptedKey = $encrypter->wrapKey(
                contentEncryptionKey: $cek,
            );
        }

        $contentEncrypter = ContentEncrypterFactory::createFromAlgorithm(
            algorithm: $contentAlgorithm,
        );

        $result = $contentEncrypter->encrypt(
            plaintext: $plaintext,
            contentEncryptionKey: $cek,
            additionalAuthenticatedData: $encodedHeader,
        );

        $compact = $encodedHeader
            . '.' . $this->base64UrlEncode(bytes: $encryptedKey)
            . '.' . $this->base64UrlEncode(bytes: $result->initializationVector)
            . '.' . $this->base64UrlEncode(bytes: $result->ciphertext)
            . '.' . $this->base64UrlEncode(bytes: $result->authenticationTag);

        return [
            'headerData' => $headerData,
            'encryptedKey' => $encryptedKey,
            'initializationVector' => $result->initializationVector,
            'ciphertext' => $result->ciphertext,
            'authenticationTag' => $result->authenticationTag,
            'compact' => $compact,
        ];
    }

    public function parseEncrypted(
        string $compact,
    ): JweTokenInterface {
        $segments = \explode('.', $compact);

        if (\sizeof($segments) !== 5) {
            throw JwtException::fromMalformedToken(
                token: $compact,
            );
        }

        [$encodedHeader, $encodedEncryptedKey, $encodedIv, $encodedCiphertext, $encodedTag] = $segments;

        $headerData = $this->jsonDecode(
            json: $this->base64UrlDecode($encodedHeader),
        );

        return new JweToken(
            header: new Header($headerData),
            claims: new Claims([]),
            encryptedKey: $this->base64UrlDecode($encodedEncryptedKey),
            initializationVector: $this->base64UrlDecode($encodedIv),
            ciphertext: $this->base64UrlDecode($encodedCiphertext),
            authenticationTag: $this->base64UrlDecode($encodedTag),
            compact: $compact,
        );
    }

    public function decrypt(
        string $compact,
        KeyInterface $key,
        ConstraintInterface ...$constraints,
    ): JweTokenInterface {
        $decrypted = $this->performDecryption(
            compact: $compact,
            key: $key,
            constraints: $constraints,
        );

        $claimsData = $this->jsonDecode(
            json: $decrypted['plaintext'],
        );

        $token = new JweToken(
            header: $decrypted['header'],
            claims: new Claims($claimsData),
            encryptedKey: $decrypted['encryptedKey'],
            initializationVector: $decrypted['initializationVector'],
            ciphertext: $decrypted['ciphertext'],
            authenticationTag: $decrypted['authenticationTag'],
            compact: $compact,
        );

        foreach ($constraints as $constraint) {
            $constraint->check(
                token: $token,
            );
        }

        return $token;
    }

    public function decryptAndDecode(
        string $compact,
        KeyInterface $decryptionKey,
        ConstraintInterface ...$constraints,
    ): JwsTokenInterface {
        $decrypted = $this->performDecryption(
            compact: $compact,
            key: $decryptionKey,
            constraints: $constraints,
        );

        $cty = $decrypted['header']->get('cty');

        if (!\is_string($cty) || \strcasecmp($cty, 'JWT') !== 0) {
            throw JwtException::fromNestedContentTypeMismatch(
                expected: 'JWT',
                given: \is_string($cty)
                    ? $cty
                    : '',
            );
        }

        $innerConstraints = [];

        foreach ($constraints as $constraint) {
            if ($constraint instanceof EncryptedWith) {
                continue;
            }

            $innerConstraints[] = $constraint;
        }

        return $this->decode(
            $decrypted['plaintext'],
            ...$innerConstraints,
        );
    }

    /**
     * @param array<ConstraintInterface> $constraints
     * @return array{header: Header, plaintext: string, encryptedKey: string, initializationVector: string, ciphertext: string, authenticationTag: string}
     *
     * @throws JwtException
     */
    private function performDecryption(
        string $compact,
        KeyInterface $key,
        array $constraints,
    ): array {
        $encryptedWith = null;

        foreach ($constraints as $constraint) {
            if ($constraint instanceof EncryptedWith) {
                $encryptedWith = $constraint;

                break;
            }
        }

        if ($encryptedWith === null) {
            throw JwtException::fromMissingEncryptionConstraint();
        }

        $keyAlgorithm = $encryptedWith->keyAlgorithm;
        $contentAlgorithm = $encryptedWith->contentAlgorithm;

        $segments = \explode('.', $compact);

        if (\sizeof($segments) !== 5) {
            throw JwtException::fromMalformedToken(
                token: $compact,
            );
        }

        [$encodedHeader, $encodedEncryptedKey, $encodedIv, $encodedCiphertext, $encodedTag] = $segments;

        $headerData = $this->jsonDecode(
            json: $this->base64UrlDecode($encodedHeader),
        );
        $header = new Header($headerData);

        if (!$keyAlgorithm->is($header->algorithm)) {
            throw JwtException::fromAlgorithmMismatch(
                expected: $keyAlgorithm->identifier(),
                given: $header->algorithm,
            );
        }

        $encHeaderValue = $header->get('enc');

        if (!\is_string($encHeaderValue) || !$contentAlgorithm->is($encHeaderValue)) {
            throw JwtException::fromAlgorithmMismatch(
                expected: $contentAlgorithm->identifier(),
                given: \is_string($encHeaderValue)
                    ? $encHeaderValue
                    : '',
            );
        }

        $encryptedKey = $this->base64UrlDecode($encodedEncryptedKey);
        $iv = $this->base64UrlDecode($encodedIv);
        $ciphertext = $this->base64UrlDecode($encodedCiphertext);
        $tag = $this->base64UrlDecode($encodedTag);

        if ($keyAlgorithm === KeyManagementAlgorithm::DIR) {
            if (!$key instanceof SymmetricKey) {
                throw JwtException::fromIncompatibleKey(
                    algorithm: $keyAlgorithm->identifier(),
                    expected: SymmetricKey::class,
                    given: $key::class,
                );
            }

            $expectedCekLength = $contentAlgorithm->keyLengthBytes();
            $actualCekLength = \strlen($key->secret);

            if ($actualCekLength !== $expectedCekLength) {
                throw JwtException::fromInvalidSymmetricKeyLength(
                    algorithm: $keyAlgorithm->identifier() . '+' . $contentAlgorithm->identifier(),
                    expectedBytes: \strval($expectedCekLength),
                    actualBytes: $actualCekLength,
                );
            }

            $cek = $key->secret;
        } else {
            $decrypter = DecrypterFactory::createFromAlgorithm(
                algorithm: $keyAlgorithm,
                key: $key,
            );
            $cek = $decrypter->unwrapKey(
                wrappedKey: $encryptedKey,
            );
        }

        $contentDecrypter = ContentDecrypterFactory::createFromAlgorithm(
            algorithm: $contentAlgorithm,
        );

        $plaintext = $contentDecrypter->decrypt(
            ciphertext: $ciphertext,
            initializationVector: $iv,
            authenticationTag: $tag,
            contentEncryptionKey: $cek,
            additionalAuthenticatedData: $encodedHeader,
        );

        return [
            'header' => $header,
            'plaintext' => $plaintext,
            'encryptedKey' => $encryptedKey,
            'initializationVector' => $iv,
            'ciphertext' => $ciphertext,
            'authenticationTag' => $tag,
        ];
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
