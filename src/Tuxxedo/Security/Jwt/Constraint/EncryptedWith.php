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

use Tuxxedo\Security\Jwt\ContentEncryptionAlgorithm;
use Tuxxedo\Security\Jwt\JweTokenInterface;
use Tuxxedo\Security\Jwt\JwtException;
use Tuxxedo\Security\Jwt\KeyManagementAlgorithm;
use Tuxxedo\Security\Jwt\TokenInterface;

class EncryptedWith implements ConstraintInterface
{
    public function __construct(
        public readonly KeyManagementAlgorithm $keyAlgorithm,
        public readonly ContentEncryptionAlgorithm $contentAlgorithm,
    ) {
    }

    public function check(
        TokenInterface $token,
    ): void {
        if (!$token instanceof JweTokenInterface) {
            throw JwtException::fromWrongTokenType(
                expected: JweTokenInterface::class,
                actual: $token::class,
            );
        }

        if (!$this->keyAlgorithm->is($token->header->algorithm)) {
            throw JwtException::fromAlgorithmMismatch(
                expected: $this->keyAlgorithm->identifier(),
                given: $token->header->algorithm,
            );
        }

        $encValue = $token->header->get('enc');

        if (!\is_string($encValue) || !$this->contentAlgorithm->is($encValue)) {
            throw JwtException::fromAlgorithmMismatch(
                expected: $this->contentAlgorithm->identifier(),
                given: \is_string($encValue)
                    ? $encValue
                    : '',
            );
        }
    }
}
