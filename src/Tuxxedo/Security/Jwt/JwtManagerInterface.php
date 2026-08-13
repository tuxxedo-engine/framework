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

// @todo Support JWE
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
    ): TokenInterface;

    /**
     * @throws JwtException
     */
    public function parse(
        string $compact,
    ): TokenInterface;

    /**
     * @throws JwtException
     */
    public function decode(
        string $compact,
        ConstraintInterface ...$constraints,
    ): TokenInterface;
}
