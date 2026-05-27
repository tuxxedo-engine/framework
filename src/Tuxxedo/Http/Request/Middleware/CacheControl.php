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

use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\ResponseInterface;

#[\Attribute(flags: \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class CacheControl implements MiddlewareInterface
{
    public function __construct(
        public readonly ?int $maxAge = null,
        public readonly ?int $sMaxAge = null,
        public readonly bool $public = false,
        public readonly bool $private = false,
        public readonly bool $noCache = false,
        public readonly bool $noStore = false,
        public readonly bool $mustRevalidate = false,
        public readonly bool $proxyRevalidate = false,
        public readonly bool $immutable = false,
        public readonly ?int $staleWhileRevalidate = null,
        public readonly ?int $staleIfError = null,
        public readonly bool $onlyIfMissing = true,
    ) {
    }

    public function handle(
        RequestInterface $request,
        MiddlewareInterface $next,
    ): ResponseInterface {
        $response = $next->handle($request, $next);

        if ($this->onlyIfMissing && $response->hasHeader('Cache-Control')) {
            return $response;
        }

        return $response->withCacheControl(
            maxAge: $this->maxAge,
            sMaxAge: $this->sMaxAge,
            public: $this->public,
            private: $this->private,
            noCache: $this->noCache,
            noStore: $this->noStore,
            mustRevalidate: $this->mustRevalidate,
            proxyRevalidate: $this->proxyRevalidate,
            immutable: $this->immutable,
            staleWhileRevalidate: $this->staleWhileRevalidate,
            staleIfError: $this->staleIfError,
        );
    }
}
