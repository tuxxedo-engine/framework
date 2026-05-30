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

use Tuxxedo\Http\Method;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\ResponseInterface;

#[\Attribute(flags: \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
readonly class MethodOverride implements MiddlewareInterface
{
    public function __construct(
        public string $field = '_method',
        public string $header = 'X-HTTP-Method-Override',
    ) {
    }

    public function handle(
        RequestInterface $request,
        MiddlewareInterface $next,
    ): ResponseInterface {
        if ($request->method !== Method::POST) {
            return $next->handle($request, $next);
        }

        $override = $this->resolveOverride($request);

        if ($override === null) {
            return $next->handle($request, $next);
        }

        return $next->handle(
            request: $request->withMethod($override),
            next: $next,
        );
    }

    private function resolveOverride(
        RequestInterface $request,
    ): ?Method {
        if ($request->post->has($this->field)) {
            return $this->parseMethod($request->post->string($this->field));
        }

        if ($request->headers->has($this->header)) {
            return $this->parseMethod($request->headers->string($this->header));
        }

        return null;
    }

    private function parseMethod(
        string $value,
    ): ?Method {
        $value = \trim($value);

        if ($value === '') {
            return null;
        }

        foreach (Method::cases() as $case) {
            if (\strcasecmp($case->name, $value) === 0) {
                return $case;
            }
        }

        return null;
    }
}
