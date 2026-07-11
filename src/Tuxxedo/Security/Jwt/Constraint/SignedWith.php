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

namespace Tuxxedo\Security\Jwt\Constraint;

use Tuxxedo\Security\Jwt\Algorithm;
use Tuxxedo\Security\Jwt\JwtException;
use Tuxxedo\Security\Jwt\Key\KeyInterface;
use Tuxxedo\Security\Jwt\Key\KeySetInterface;
use Tuxxedo\Security\Jwt\TokenInterface;
use Tuxxedo\Security\Jwt\Verifier\VerifierFactory;
use Tuxxedo\Security\Jwt\Verifier\VerifierInterface;

class SignedWith implements ConstraintInterface
{
    private readonly ?VerifierInterface $eagerVerifier;

    /**
     * @throws JwtException
     */
    public function __construct(
        private readonly Algorithm $algorithm,
        private readonly KeyInterface|KeySetInterface $key,
    ) {
        $this->eagerVerifier = $key instanceof KeyInterface
            ? VerifierFactory::createFromAlgorithm(
                algorithm: $algorithm,
                key: $key,
            )
            : null;
    }

    public function check(
        TokenInterface $token,
    ): void {
        if (!$this->algorithm->is($token->header->algorithm)) {
            throw JwtException::fromAlgorithmMismatch(
                expected: $this->algorithm->identifier(),
                given: $token->header->algorithm,
            );
        }

        $lastDot = \strrpos($token->compact, '.');

        if ($lastDot === false) {
            throw JwtException::fromMalformedToken(
                token: $token->compact,
            );
        }

        $signingInput = \substr($token->compact, 0, $lastDot);
        $verifier = $this->eagerVerifier;

        if ($verifier === null) {
            $verifier = VerifierFactory::createFromAlgorithm(
                algorithm: $this->algorithm,
                key: $this->resolveKey(
                    token: $token,
                ),
            );
        }

        if (!$verifier->verify($signingInput, $token->signature)) {
            throw JwtException::fromSignatureMismatch();
        }
    }

    /**
     * @throws JwtException
     */
    private function resolveKey(
        TokenInterface $token,
    ): KeyInterface {
        /** @var KeySetInterface $keySet */
        $keySet = $this->key;

        $keyId = $token->header->keyId;

        if ($keyId === null) {
            throw JwtException::fromMissingHeader(
                header: 'kid',
            );
        }

        $resolved = $keySet->find(
            keyId: $keyId,
        );

        if ($resolved === null) {
            throw JwtException::fromNoMatchingKey(
                keyId: $keyId,
            );
        }

        return $resolved;
    }
}
