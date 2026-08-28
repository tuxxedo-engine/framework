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

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;
use Tuxxedo\Security\Jwt\Constraint\ConstraintInterface;
use Tuxxedo\Security\Jwt\Key\KeyInterface;

#[DefaultImplementation(class: JwtManager::class, lifecycle: Lifecycle::SINGLETON)]
interface JwtManagerInterface
{
    /**
     * @param array<string, mixed> $claims
     * @param array<string, mixed> $extraHeader
     *
     * @throws JwtException
     */
    public function encode(
        array $claims,
        Algorithm $algorithm,
        KeyInterface $key,
        array $extraHeader = [],
    ): JwsTokenInterface;

    /**
     * @throws JwtException
     */
    public function parse(
        string $compact,
    ): JwsTokenInterface;

    /**
     * @throws JwtException
     */
    public function decode(
        string $compact,
        ConstraintInterface ...$constraints,
    ): JwsTokenInterface;

    /**
     * @param array<string, mixed> $claims
     * @param array<string, mixed> $extraHeader
     *
     * @throws JwtException
     */
    public function encrypt(
        array $claims,
        KeyManagementAlgorithm $keyAlgorithm,
        ContentEncryptionAlgorithm $contentAlgorithm,
        KeyInterface $key,
        array $extraHeader = [],
    ): JweTokenInterface;

    /**
     * @throws JwtException
     */
    public function parseEncrypted(
        string $compact,
    ): JweTokenInterface;

    /**
     * @throws JwtException
     */
    public function decrypt(
        string $compact,
        KeyInterface $key,
        ConstraintInterface ...$constraints,
    ): JweTokenInterface;

    /**
     * @param array<string, mixed> $claims
     * @param array<string, mixed> $extraHeader
     *
     * @throws JwtException
     */
    public function encodeAndEncrypt(
        array $claims,
        Algorithm $signingAlgorithm,
        KeyInterface $signingKey,
        KeyManagementAlgorithm $keyAlgorithm,
        ContentEncryptionAlgorithm $contentAlgorithm,
        KeyInterface $encryptionKey,
        array $extraHeader = [],
    ): JweTokenInterface;

    /**
     * @throws JwtException
     */
    public function decryptAndDecode(
        string $compact,
        KeyInterface $decryptionKey,
        ConstraintInterface ...$constraints,
    ): JwsTokenInterface;
}
