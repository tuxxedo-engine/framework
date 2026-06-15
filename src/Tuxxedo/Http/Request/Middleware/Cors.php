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

use Tuxxedo\Http\Header;
use Tuxxedo\Http\HttpException;
use Tuxxedo\Http\Method;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseCode;
use Tuxxedo\Http\Response\ResponseInterface;

#[\Attribute(flags: \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
readonly class Cors implements MiddlewareInterface
{
    private bool $wildcardOrigin;
    private string $allowedMethodsHeader;
    private string $allowedHeadersHeader;
    private string $exposedHeadersHeader;

    /**
     * @param string[] $allowedOrigins
     * @param Method[] $allowedMethods
     * @param string[] $allowedHeaders
     * @param string[] $exposedHeaders
     *
     * @throws HttpException
     */
    public function __construct(
        private array $allowedOrigins = [
            '*',
        ],
        array $allowedMethods = [
            Method::GET,
            Method::HEAD,
            Method::POST,
            Method::PUT,
            Method::PATCH,
            Method::DELETE,
        ],
        array $allowedHeaders = [
            'Content-Type',
            'Authorization',
            'X-Requested-With',
        ],
        array $exposedHeaders = [],
        private bool $allowCredentials = false,
        private int $maxAge = 600,
    ) {
        $wildcard = \sizeof($allowedOrigins) === 1 && \array_first($allowedOrigins) === '*';

        if ($wildcard && $allowCredentials) {
            throw HttpException::fromInternalServerError();
        }

        $this->wildcardOrigin = $wildcard;
        $this->allowedMethodsHeader = \join(', ', \array_map(
            static fn (Method $method): string => $method->name,
            $allowedMethods,
        ));

        $this->allowedHeadersHeader = \join(', ', $allowedHeaders);
        $this->exposedHeadersHeader = \join(', ', $exposedHeaders);
    }

    public function handle(
        RequestInterface $request,
        MiddlewareInterface $next,
    ): ResponseInterface {
        if (!$request->headers->has('Origin')) {
            return $next->handle($request, $next);
        }

        $origin = $request->headers->string('Origin');
        $allowOrigin = $this->resolveAllowOrigin($origin);

        if (
            $request->method === Method::OPTIONS &&
            $request->headers->has('Access-Control-Request-Method')
        ) {
            return $this->buildPreflightResponse($allowOrigin);
        }

        $response = $next->handle($request, $next);

        if ($allowOrigin === null) {
            return $response;
        }

        return $this->decorateResponse($response, $allowOrigin);
    }

    private function resolveAllowOrigin(
        string $origin,
    ): ?string {
        if ($this->wildcardOrigin) {
            return '*';
        }

        foreach ($this->allowedOrigins as $allowed) {
            if ($allowed === $origin) {
                return $origin;
            }
        }

        return null;
    }

    private function buildPreflightResponse(
        ?string $allowOrigin,
    ): ResponseInterface {
        $response = Response::empty(
            responseCode: ResponseCode::NO_CONTENT,
        )
            ->withHeader(new Header('Access-Control-Allow-Methods', $this->allowedMethodsHeader))
            ->withHeader(new Header('Access-Control-Allow-Headers', $this->allowedHeadersHeader))
            ->withHeader(new Header('Access-Control-Max-Age', \strval($this->maxAge)));

        if ($allowOrigin !== null) {
            $response = $response->withHeader(
                new Header('Access-Control-Allow-Origin', $allowOrigin),
            );

            if ($allowOrigin !== '*') {
                $response = $response->withVary('Origin');
            }
        }

        if ($this->allowCredentials) {
            $response = $response->withHeader(
                new Header('Access-Control-Allow-Credentials', 'true'),
            );
        }

        return $response;
    }

    private function decorateResponse(
        ResponseInterface $response,
        string $allowOrigin,
    ): ResponseInterface {
        $response = $response->withHeader(
            new Header('Access-Control-Allow-Origin', $allowOrigin),
        );

        if ($allowOrigin !== '*') {
            $response = $response->withVary('Origin');
        }

        if ($this->exposedHeadersHeader !== '') {
            $response = $response->withHeader(
                new Header('Access-Control-Expose-Headers', $this->exposedHeadersHeader),
            );
        }

        if ($this->allowCredentials) {
            $response = $response->withHeader(
                new Header('Access-Control-Allow-Credentials', 'true'),
            );
        }

        return $response;
    }
}
