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

namespace Tuxxedo\Http\Client\Rules;

use Tuxxedo\Http\Client\HttpClientException;
use Tuxxedo\Http\Client\Request\ClientRequestInterface;
use Tuxxedo\Http\Client\Response\ClientResponseInterface;
use Tuxxedo\Net\IpRangeList;
use Tuxxedo\Net\IpRangeListInterface;
use Tuxxedo\Net\NetException;

readonly class Rule
{
    public ?string $method;
    public ?string $scheme;
    public ?string $hostPattern;
    public ?IpRangeListInterface $network;
    public ?string $pathPattern;

    /**
     * @param (\Closure(ClientRequestInterface): (ClientResponseInterface|HttpClientException))|null $handler
     *
     * @throws NetException
     */
    public function __construct(
        public string $pattern,
        public RuleAction $action,
        public ?\Closure $handler = null,
    ) {
        $rest = \trim($pattern);
        $method = null;
        $scheme = null;

        if (\str_contains($rest, ' ')) {
            [$method, $rest] = \explode(' ', $rest, 2);
            $method = \strtoupper($method);
            $rest = \ltrim($rest);
        }

        if (\str_contains($rest, '://')) {
            [$scheme, $rest] = \explode('://', $rest, 2);
            $scheme = \strtolower($scheme);
        }

        $this->method = $method;
        $this->scheme = $scheme;

        if (\preg_match('#^([0-9a-f.:]+)/\d{1,3}$#i', $rest, $matches) === 1 && \inet_pton($matches[1]) !== false) {
            $this->network = new IpRangeList(
                [
                    $rest,
                ],
            );

            $this->hostPattern = null;
            $this->pathPattern = null;

            return;
        }

        $slash = \strpos($rest, '/');
        $host = $slash === false
            ? $rest
            : \substr($rest, 0, $slash);

        $this->network = null;
        $this->hostPattern = '#^' . \str_replace('\*', '[^.]+(?:\.[^.]+)*', \preg_quote(\strtolower($host), '#')) . '$#';
        $this->pathPattern = $slash === false
            ? null
            : '#^' . \str_replace('\*', '.*', \preg_quote(\substr($rest, $slash), '#')) . '$#';
    }

    public function matches(
        ClientRequestInterface $request,
    ): bool {
        $url = $request->url;
        $host = \strtolower(\trim($url->host, '[]'));

        if ($this->method !== null && $this->method !== $request->method->name) {
            return false;
        }

        if ($this->scheme !== null && $this->scheme !== \strtolower($url->scheme)) {
            return false;
        }

        if ($this->network !== null) {
            return \inet_pton($host) !== false && $this->network->contains($host);
        }

        if ($this->hostPattern !== null && \preg_match($this->hostPattern, $host) !== 1) {
            return false;
        }

        return $this->pathPattern === null || \preg_match(
            $this->pathPattern,
            $url->path !== ''
                ? $url->path
                : '/',
        ) === 1;
    }
}
