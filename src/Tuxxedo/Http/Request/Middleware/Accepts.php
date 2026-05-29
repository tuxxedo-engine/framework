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

#[\Attribute(flags: \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
readonly class Accepts implements MiddlewareInterface
{
    /**
     * @var non-empty-list<string>
     */
    private array $supported;

    public function __construct(
        string ...$supported,
    ) {
        if (\sizeof($supported) === 0) {
            throw HttpException::fromInternalServerError();
        }

        $this->supported = \array_values($supported);
    }

    public function handle(
        RequestInterface $request,
        MiddlewareInterface $next,
    ): ResponseInterface {
        $request->prefers(...$this->supported) ?? throw HttpException::fromNotAcceptable();

        return $next->handle($request, $next);
    }
}
