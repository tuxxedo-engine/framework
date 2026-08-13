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

namespace Tuxxedo\Http\Request\Middleware;

use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\ResponseInterface;
use Tuxxedo\Security\Jwt\Constraint\ConstraintInterface;
use Tuxxedo\Security\Jwt\JwtException;
use Tuxxedo\Security\Jwt\JwtManagerInterface;
use Tuxxedo\Security\Jwt\JwtTokenAccessor;

class Jwt implements MiddlewareInterface
{
    /**
     * @param list<ConstraintInterface> $constraints
     */
    public function __construct(
        private readonly JwtManagerInterface $manager,
        private readonly JwtTokenAccessor $accessor,
        private readonly array $constraints = [],
        private readonly bool $required = true,
    ) {
    }

    public function handle(
        RequestInterface $request,
        MiddlewareInterface $next,
    ): ResponseInterface {
        $compact = self::extractBearer($request);

        if ($compact === null) {
            if ($this->required) {
                throw HttpException::fromUnauthorized();
            }

            return $next->handle($request, $next);
        }

        try {
            $token = $this->manager->decode($compact, ...$this->constraints);
        } catch (JwtException $exception) {
            throw HttpException::fromUnauthorized($exception);
        }

        $this->accessor->setCurrent($token);

        return $next->handle($request, $next);
    }

    private static function extractBearer(
        RequestInterface $request,
    ): ?string {
        if (!$request->headers->has('Authorization')) {
            return null;
        }

        $header = $request->headers->string('Authorization');

        if (!\str_starts_with($header, 'Bearer ')) {
            return null;
        }

        $token = \substr($header, 7);

        return $token === ''
            ? null
            : $token;
    }
}
