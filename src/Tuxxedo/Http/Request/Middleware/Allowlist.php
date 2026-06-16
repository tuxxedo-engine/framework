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
use Tuxxedo\Net\IpRangeList;
use Tuxxedo\Net\IpRangeListInterface;
use Tuxxedo\Net\NetException;

#[\Attribute(flags: \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
readonly class Allowlist implements MiddlewareInterface
{
    private IpRangeListInterface $allowed;

    /**
     * @param IpRangeListInterface|string[] $entries
     *
     * @throws NetException
     */
    public function __construct(
        IpRangeListInterface|array $entries,
    ) {
        $this->allowed = $entries instanceof IpRangeListInterface
            ? $entries
            : new IpRangeList($entries);
    }

    public function handle(
        RequestInterface $request,
        MiddlewareInterface $next,
    ): ResponseInterface {
        if (!$this->allowed->contains($request->ipAddress)) {
            throw HttpException::fromForbidden();
        }

        return $next->handle($request, $next);
    }
}
