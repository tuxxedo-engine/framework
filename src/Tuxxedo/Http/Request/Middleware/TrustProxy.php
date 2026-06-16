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
use Tuxxedo\Net\IpRangeList;
use Tuxxedo\Net\IpRangeListInterface;
use Tuxxedo\Net\NetException;

#[\Attribute(flags: \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
readonly class TrustProxy implements MiddlewareInterface
{
    private IpRangeListInterface $trustedProxies;

    /**
     * @var list<TrustedHeader>
     */
    private array $trustedHeaders;

    /**
     * @param IpRangeListInterface|string[] $trustedProxies
     * @param TrustedHeader[] $trustedHeaders
     *
     * @throws NetException
     */
    public function __construct(
        IpRangeListInterface|array $trustedProxies,
        array $trustedHeaders = [
            TrustedHeader::CLIENT_IP,
            TrustedHeader::PROTO,
            TrustedHeader::HOST,
            TrustedHeader::PORT,
        ],
    ) {
        $this->trustedProxies = $trustedProxies instanceof IpRangeListInterface
            ? $trustedProxies
            : new IpRangeList($trustedProxies);

        $this->trustedHeaders = \array_values($trustedHeaders);
    }

    public function handle(
        RequestInterface $request,
        MiddlewareInterface $next,
    ): ResponseInterface {
        if (!$this->trustedProxies->contains($request->ipAddress)) {
            return $next->handle($request, $next);
        }

        if ($request->headers->has('Forwarded')) {
            $request = $this->applyForwarded($request);
        } else {
            $request = $this->applyXForwarded($request);
        }

        return $next->handle($request, $next);
    }

    private function isHeaderTrusted(
        TrustedHeader $header,
    ): bool {
        return \in_array($header, $this->trustedHeaders, true);
    }

    private function applyXForwarded(
        RequestInterface $request,
    ): RequestInterface {
        return $request
            |> $this->applyXForwardedClientIp(...)
            |> $this->applyXForwardedProto(...)
            |> $this->applyXForwardedHost(...)
            |> $this->applyXForwardedPort(...);
    }

    private function applyXForwardedClientIp(
        RequestInterface $request,
    ): RequestInterface {
        if (!$this->isHeaderTrusted(TrustedHeader::CLIENT_IP)) {
            return $request;
        }

        $clientIp = $this->resolveClientIpFromXForwardedFor($request);

        if ($clientIp !== null) {
            return $request->withIpAddress($clientIp);
        }

        $clientIp = $this->resolveClientIpFromXRealIp($request);

        if ($clientIp !== null) {
            return $request->withIpAddress($clientIp);
        }

        return $request;
    }

    private function resolveClientIpFromXForwardedFor(
        RequestInterface $request,
    ): ?string {
        if (!$request->headers->has('X-Forwarded-For')) {
            return null;
        }

        $chain = $request->headers->string('X-Forwarded-For');
        $entries = [];

        foreach (\explode(',', $chain) as $rawEntry) {
            $entry = \trim($rawEntry);

            if ($entry === '' || \inet_pton($entry) === false) {
                continue;
            }

            $entries[] = $entry;
        }

        if (\sizeof($entries) === 0) {
            return null;
        }

        for ($i = \sizeof($entries) - 1; $i >= 0; $i--) {
            if (!$this->trustedProxies->contains($entries[$i])) {
                return $entries[$i];
            }
        }

        return $entries[0];
    }

    private function resolveClientIpFromXRealIp(
        RequestInterface $request,
    ): ?string {
        if (!$request->headers->has('X-Real-IP')) {
            return null;
        }

        $value = \trim($request->headers->string('X-Real-IP'));

        if ($value === '' || \inet_pton($value) === false) {
            return null;
        }

        return $value;
    }

    private function applyXForwardedProto(
        RequestInterface $request,
    ): RequestInterface {
        if (!$this->isHeaderTrusted(TrustedHeader::PROTO)) {
            return $request;
        }

        if (!$request->headers->has('X-Forwarded-Proto')) {
            return $request;
        }

        $proto = \strtolower(
            \trim($request->headers->string('X-Forwarded-Proto')),
        );

        return $request->withHttps($proto === 'https');
    }

    private function applyXForwardedHost(
        RequestInterface $request,
    ): RequestInterface {
        if (!$this->isHeaderTrusted(TrustedHeader::HOST)) {
            return $request;
        }

        if (!$request->headers->has('X-Forwarded-Host')) {
            return $request;
        }

        $parsed = $this->parseHostHeader(
            value: $request->headers->string('X-Forwarded-Host'),
        );

        if ($parsed === null) {
            return $request;
        }

        $request = $request->withHost($parsed['host']);

        if ($parsed['port'] !== null && $this->isHeaderTrusted(TrustedHeader::PORT)) {
            $request = $request->withPort($parsed['port']);
        }

        return $request;
    }

    private function applyXForwardedPort(
        RequestInterface $request,
    ): RequestInterface {
        if (!$this->isHeaderTrusted(TrustedHeader::PORT)) {
            return $request;
        }

        if (!$request->headers->has('X-Forwarded-Port')) {
            return $request;
        }

        $value = \trim($request->headers->string('X-Forwarded-Port'));

        if (!$this->isPositiveInteger($value)) {
            return $request;
        }

        return $request->withPort((int) $value);
    }

    private function applyForwarded(
        RequestInterface $request,
    ): RequestInterface {
        $entries = $this->parseForwarded(
            header: $request->headers->string('Forwarded'),
        );

        if (\sizeof($entries) === 0) {
            return $request;
        }

        $clientEntry = $this->selectForwardedClientEntry($entries);

        $request = $this->applyForwardedClientIp($request, $clientEntry);
        $request = $this->applyForwardedProto($request, $clientEntry);
        $request = $this->applyForwardedHost($request, $clientEntry);

        return $request;
    }

    /**
     * @param array<string, string> $entry
     */
    private function applyForwardedClientIp(
        RequestInterface $request,
        array $entry,
    ): RequestInterface {
        if (!$this->isHeaderTrusted(TrustedHeader::CLIENT_IP)) {
            return $request;
        }

        if (!isset($entry['for'])) {
            return $request;
        }

        $clientIp = $this->extractIpFromForwardedValue($entry['for']);

        if ($clientIp === null) {
            return $request;
        }

        return $request->withIpAddress($clientIp);
    }

    /**
     * @param array<string, string> $entry
     */
    private function applyForwardedProto(
        RequestInterface $request,
        array $entry,
    ): RequestInterface {
        if (!$this->isHeaderTrusted(TrustedHeader::PROTO)) {
            return $request;
        }

        if (!isset($entry['proto'])) {
            return $request;
        }

        $proto = \strtolower(\trim($entry['proto']));

        return $request->withHttps($proto === 'https');
    }

    /**
     * @param array<string, string> $entry
     */
    private function applyForwardedHost(
        RequestInterface $request,
        array $entry,
    ): RequestInterface {
        if (!$this->isHeaderTrusted(TrustedHeader::HOST)) {
            return $request;
        }

        if (!isset($entry['host'])) {
            return $request;
        }

        $parsed = $this->parseHostHeader(
            value: $entry['host'],
        );

        if ($parsed === null) {
            return $request;
        }

        $request = $request->withHost($parsed['host']);

        if ($parsed['port'] !== null && $this->isHeaderTrusted(TrustedHeader::PORT)) {
            $request = $request->withPort($parsed['port']);
        }

        return $request;
    }

    /**
     * @return list<array<string, string>>
     */
    private function parseForwarded(
        string $header,
    ): array {
        $entries = $this->splitRespectingQuotes($header, ',');
        $result = [];

        foreach ($entries as $rawEntry) {
            $entry = \trim($rawEntry);

            if ($entry === '') {
                continue;
            }

            $pairs = $this->splitRespectingQuotes($entry, ';');
            $entryPairs = [];

            foreach ($pairs as $rawPair) {
                $pair = \trim($rawPair);
                $eq = \strpos($pair, '=');

                if ($eq === false) {
                    continue;
                }

                $key = \strtolower(\trim(\substr($pair, 0, $eq)));
                $value = $this->unquote(\trim(\substr($pair, $eq + 1)));

                if ($key === '') {
                    continue;
                }

                $entryPairs[$key] = $value;
            }

            if (\sizeof($entryPairs) > 0) {
                $result[] = $entryPairs;
            }
        }

        return $result;
    }

    /**
     * @param non-empty-list<array<string, string>> $entries
     * @return array<string, string>
     */
    private function selectForwardedClientEntry(
        array $entries,
    ): array {
        for ($i = \sizeof($entries) - 1; $i >= 0; $i--) {
            $entry = $entries[$i];

            if (!isset($entry['for'])) {
                continue;
            }

            $ip = $this->extractIpFromForwardedValue($entry['for']);

            if ($ip === null) {
                continue;
            }

            if (!$this->trustedProxies->contains($ip)) {
                return $entry;
            }
        }

        return $entries[0];
    }

    private function extractIpFromForwardedValue(
        string $value,
    ): ?string {
        $value = \trim($value);

        if ($value === '' || $value === 'unknown' || $value[0] === '_') {
            return null;
        }

        if ($value[0] === '[') {
            $closing = \strpos($value, ']');

            if ($closing === false) {
                return null;
            }

            $extracted = \substr($value, 1, $closing - 1);
        } else {
            $firstColon = \strpos($value, ':');
            $lastColon = \strrpos($value, ':');

            if ($firstColon !== false && $firstColon === $lastColon) {
                $extracted = \substr($value, 0, $lastColon);
            } else {
                $extracted = $value;
            }
        }

        if (\inet_pton($extracted) === false) {
            return null;
        }

        return $extracted;
    }

    /**
     * @return array{host: string, port: ?int}|null
     */
    private function parseHostHeader(
        string $value,
    ): ?array {
        $value = \trim($value);

        if ($value === '') {
            return null;
        }

        if ($value[0] === '[') {
            $closing = \strpos($value, ']');

            if ($closing === false) {
                return null;
            }

            $host = \substr($value, 1, $closing - 1);
            $remainder = \substr($value, $closing + 1);
            $port = null;

            if ($remainder !== '' && $remainder[0] === ':') {
                $portStr = \substr($remainder, 1);

                if ($this->isPositiveInteger($portStr)) {
                    $port = (int) $portStr;
                }
            }

            return [
                'host' => $host,
                'port' => $port,
            ];
        }

        $firstColon = \strpos($value, ':');
        $lastColon = \strrpos($value, ':');

        if ($firstColon === false || $firstColon !== $lastColon) {
            return [
                'host' => $value,
                'port' => null,
            ];
        }

        $portStr = \substr($value, $lastColon + 1);

        if (!$this->isPositiveInteger($portStr)) {
            return [
                'host' => $value,
                'port' => null,
            ];
        }

        return [
            'host' => \substr($value, 0, $lastColon),
            'port' => (int) $portStr,
        ];
    }

    private function isPositiveInteger(
        string $value,
    ): bool {
        return $value !== '' && \strspn($value, '0123456789') === \strlen($value);
    }

    /**
     * @return list<string>
     */
    private function splitRespectingQuotes(
        string $input,
        string $separator,
    ): array {
        $result = [];
        $current = '';
        $inQuote = false;
        $length = \strlen($input);

        for ($i = 0; $i < $length; $i++) {
            $char = $input[$i];

            if ($char === '"' && ($i === 0 || $input[$i - 1] !== '\\')) {
                $inQuote = !$inQuote;
                $current .= $char;
            } elseif ($char === $separator && !$inQuote) {
                $result[] = $current;
                $current = '';
            } else {
                $current .= $char;
            }
        }

        $result[] = $current;

        return $result;
    }

    private function unquote(
        string $value,
    ): string {
        $length = \strlen($value);

        if ($length < 2 || $value[0] !== '"' || $value[$length - 1] !== '"') {
            return $value;
        }

        return \str_replace(
            [
                '\\"',
                '\\\\',
            ],
            [
                '"',
                '\\',
            ],
            \substr($value, 1, -1),
        );
    }
}
