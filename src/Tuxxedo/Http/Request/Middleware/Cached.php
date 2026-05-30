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
use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseCode;
use Tuxxedo\Http\Response\ResponseInterface;

#[\Attribute(flags: \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
readonly class Cached implements MiddlewareInterface
{
    /**
     * @param (\Closure(RequestInterface): ?string)|string|null $etag
     * @param (\Closure(RequestInterface): ?\DateTimeInterface)|\DateTimeInterface|null $lastModified
     */
    public function __construct(
        public \Closure|string|null $etag = null,
        public \Closure|\DateTimeInterface|null $lastModified = null,
    ) {
    }

    public function handle(
        RequestInterface $request,
        MiddlewareInterface $next,
    ): ResponseInterface {
        $method = $request->method;

        if ($method !== Method::GET && $method !== Method::HEAD) {
            return $next->handle($request, $next);
        }

        $etag = $this->resolveEtag($request);
        $lastModified = $this->resolveLastModified($request);

        if ($request->isNotModified($etag, $lastModified)) {
            $response = Response::empty(
                responseCode: ResponseCode::NOT_MODIFIED,
            );

            if ($etag !== null) {
                $response = $response->withEtag($etag);
            }

            if ($lastModified !== null) {
                $response = $response->withLastModified($lastModified);
            }

            return $response;
        }

        $response = $next->handle($request, $next);

        if ($etag !== null && !$response->hasHeader('ETag')) {
            $response = $response->withEtag($etag);
        }

        if ($lastModified !== null && !$response->hasHeader('Last-Modified')) {
            $response = $response->withLastModified($lastModified);
        }

        return $response;
    }

    private function resolveEtag(
        RequestInterface $request,
    ): ?string {
        if ($this->etag === null) {
            return null;
        }

        if (\is_string($this->etag)) {
            return $this->etag;
        }

        return ($this->etag)($request);
    }

    private function resolveLastModified(
        RequestInterface $request,
    ): ?\DateTimeInterface {
        if ($this->lastModified === null) {
            return null;
        }

        if ($this->lastModified instanceof \DateTimeInterface) {
            return $this->lastModified;
        }

        return ($this->lastModified)($request);
    }
}
