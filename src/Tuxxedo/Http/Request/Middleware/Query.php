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
use Tuxxedo\Http\Method;
use Tuxxedo\Http\Request\RequestInterface;
use Tuxxedo\Http\Response\Response;
use Tuxxedo\Http\Response\ResponseCode;
use Tuxxedo\Http\Response\ResponseInterface;

#[\Attribute(flags: \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
readonly class Query implements MiddlewareInterface
{
    /**
     * @var list<string>
     */
    private array $accepted;

    public function __construct(
        string ...$acceptedMediaTypes,
    ) {
        $this->accepted = \array_values(
            \array_map(
                static fn (string $mediaType): string => \strtolower(\trim($mediaType)),
                $acceptedMediaTypes,
            ),
        );
    }

    /**
     * @throws HttpException
     */
    public function handle(
        RequestInterface $request,
        MiddlewareInterface $next,
    ): ResponseInterface {
        if ($request->method !== Method::QUERY) {
            return $next->handle($request, $next);
        }

        if (!$request->headers->has('Content-Type')) {
            throw HttpException::fromBadRequest();
        }

        $mediaType = $this->extractMediaType(
            contentType: $request->headers->string('Content-Type'),
        );

        if ($mediaType === '' || !\in_array($mediaType, $this->accepted, strict: true)) {
            return Response::empty(
                responseCode: ResponseCode::UNSUPPORTED_MEDIA_TYPE,
            )->withAcceptQuery(...$this->accepted);
        }

        return $next->handle($request, $next);
    }

    private function extractMediaType(
        string $contentType,
    ): string {
        $semicolon = \strpos($contentType, ';');

        if ($semicolon !== false) {
            $contentType = \substr($contentType, 0, $semicolon);
        }

        return \strtolower(\trim($contentType));
    }
}
