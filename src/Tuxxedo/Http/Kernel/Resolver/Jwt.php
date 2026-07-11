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

namespace Tuxxedo\Http\Kernel\Resolver;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Container\DependencyResolverInterface;
use Tuxxedo\Reflection\ParameterReflectorInterface;
use Tuxxedo\Security\Jwt\JwtException;
use Tuxxedo\Security\Jwt\JwtTokenAccessorInterface;
use Tuxxedo\Security\Jwt\TokenInterface;

/**
 * @implements DependencyResolverInterface<TokenInterface|null>
 */
#[\Attribute(flags: \Attribute::TARGET_PARAMETER)]
class Jwt implements DependencyResolverInterface
{
    /**
     * @throws JwtException
     */
    public function resolve(
        ContainerInterface $container,
        ParameterReflectorInterface $parameter,
    ): ?TokenInterface {
        $current = $container->resolve(JwtTokenAccessorInterface::class)->current();

        if ($current === null) {
            if ($parameter->isNullable()) {
                return null;
            }

            throw JwtException::fromMissingToken();
        }

        return $current;
    }
}
