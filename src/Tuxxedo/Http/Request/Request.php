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

namespace Tuxxedo\Http\Request;

use Tuxxedo\Http\InputContext;
use Tuxxedo\Http\Request\Context\BodyContextInterface;
use Tuxxedo\Http\Request\Context\EnvironmentBodyContext;
use Tuxxedo\Http\Request\Context\EnvironmentHeaderContext;
use Tuxxedo\Http\Request\Context\EnvironmentInputContext;
use Tuxxedo\Http\Request\Context\EnvironmentServerContext;
use Tuxxedo\Http\Request\Context\EnvironmentUploadedFilesContext;
use Tuxxedo\Http\Request\Context\HeaderContextInterface;
use Tuxxedo\Http\Request\Context\InputContextInterface;
use Tuxxedo\Http\Request\Context\ServerContextInterface;
use Tuxxedo\Http\Request\Context\UploadedFilesContextInterface;
use Tuxxedo\Router\DispatchableRouteInterface;

class Request implements RequestInterface
{
    public private(set) DispatchableRouteInterface $route;

    public function __construct(
        ?DispatchableRouteInterface $route = null,
        public readonly ServerContextInterface $server = new EnvironmentServerContext(),
        public readonly HeaderContextInterface $headers = new EnvironmentHeaderContext(),
        public readonly InputContextInterface $cookies = new EnvironmentInputContext(
            inputContext: InputContext::COOKIE,
        ),
        public readonly InputContextInterface $get = new EnvironmentInputContext(
            inputContext: InputContext::GET,
        ),
        public readonly InputContextInterface $post = new EnvironmentInputContext(
            inputContext: InputContext::POST,
        ),
        public readonly UploadedFilesContextInterface $files = new EnvironmentUploadedFilesContext(),
        public readonly BodyContextInterface $body = new EnvironmentBodyContext(),
    ) {
        if ($route !== null) {
            $this->route = $route;
        }
    }

    #[\NoDiscard]
    public function withRoute(
        DispatchableRouteInterface $route,
    ): static {
        return clone (
            $this,
            [
                'route' => $route,
            ],
        );
    }

    public function input(
        InputContext $context,
    ): InputContextInterface {
        return match ($context) {
            InputContext::GET => $this->get,
            InputContext::POST => $this->post,
            InputContext::COOKIE => $this->cookies,
        };
    }

    public function prefers(
        string ...$supported,
    ): ?string {
        if (!$this->headers->has('Accept')) {
            return $supported[0] ?? null;
        }

        foreach ($this->headers->getWeighted('Accept')->getWeightedPairs() as $pair) {
            if ($pair->weight === 0.0) {
                continue;
            }

            $match = $this->matchNegotiation($pair->value, $supported);

            if ($match !== null) {
                return $match;
            }
        }

        return null;
    }

    /**
     * @param string[] $supported
     */
    private function matchNegotiation(
        string $clientType,
        array $supported,
    ): ?string {
        if ($clientType === '*/*') {
            return $supported[0] ?? null;
        }

        [$clientMime, $clientSubtype] = $this->splitNegotiationType($clientType);

        foreach ($supported as $type) {
            if (\strcasecmp($type, $clientType) === 0) {
                return $type;
            }
        }

        if ($clientSubtype === '*') {
            foreach ($supported as $type) {
                [$mime,] = $this->splitNegotiationType($type);

                if (\strcasecmp($mime, $clientMime) === 0) {
                    return $type;
                }
            }
        }

        return null;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitNegotiationType(string $type): array
    {
        $parts = \explode('/', $type, 2);

        return [
            $parts[0],
            $parts[1] ?? '*',
        ];
    }

    public function accepts(
        string $mimeType,
    ): bool {
        return $this->prefers($mimeType) !== null;
    }

    public function acceptsAny(
        string ...$mimeTypes,
    ): bool {
        if (\sizeof($mimeTypes) === 0) {
            return false;
        }

        return $this->prefers(...$mimeTypes) !== null;
    }

    public function acceptsJson(): bool
    {
        return $this->accepts('application/json');
    }

    public function acceptsHtml(): bool
    {
        return $this->accepts('text/html');
    }

    public function acceptsCsv(): bool
    {
        return $this->accepts('text/csv');
    }

    public function acceptsXml(): bool
    {
        return $this->accepts('application/xml');
    }

    public function acceptsText(): bool
    {
        return $this->accepts('text/plain');
    }

    public function isModified(
        ?string $etag = null,
        ?\DateTimeInterface $lastModified = null,
    ): bool {
        return !$this->isNotModified(
            etag: $etag,
            lastModified: $lastModified,
        );
    }

    public function isNotModified(
        ?string $etag = null,
        ?\DateTimeInterface $lastModified = null,
    ): bool {
        if ($etag !== null && $this->headers->has('If-None-Match')) {
            return $this->matchesIfNoneMatch($etag);
        }

        if ($lastModified !== null && $this->headers->has('If-Modified-Since')) {
            return $this->matchesIfModifiedSince($lastModified);
        }

        return false;
    }

    private function matchesIfNoneMatch(
        string $etag,
    ): bool {
        $value = $this->headers->string('If-None-Match');

        if (\trim($value) === '*') {
            return true;
        }

        $target = $this->normalizeEtag($etag);

        foreach (\explode(',', $value) as $candidate) {
            $candidate = \trim($candidate);

            if ($candidate === '') {
                continue;
            }

            if ($this->normalizeEtag($candidate) === $target) {
                return true;
            }
        }

        return false;
    }

    private function matchesIfModifiedSince(
        \DateTimeInterface $lastModified,
    ): bool {
        $value = $this->headers->string('If-Modified-Since');
        $clientTime = \DateTimeImmutable::createFromFormat('D, d M Y H:i:s \G\M\T', $value);

        if ($clientTime === false) {
            return false;
        }

        return $lastModified->getTimestamp() <= $clientTime->getTimestamp();
    }

    private function normalizeEtag(
        string $etag,
    ): string {
        if (\str_starts_with($etag, 'W/')) {
            $etag = \substr($etag, 2);
        }

        if (\strlen($etag) >= 2 && $etag[0] === '"' && $etag[-1] === '"') {
            $etag = \substr($etag, 1, -1);
        }

        return $etag;
    }
}
