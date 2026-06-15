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
readonly class Allowlist implements MiddlewareInterface
{
    /**
     * @var list<array{packed: string, prefix: int}>
     */
    private array $resolved;

    /**
     * @param string[] $entries
     *
     * @throws HttpException
     */
    public function __construct(
        array $entries,
    ) {
        $resolved = [];

        foreach ($entries as $entry) {
            foreach ($this->resolveEntry($entry) as $resolvedEntry) {
                $resolved[] = $resolvedEntry;
            }
        }

        $this->resolved = $resolved;
    }

    public function handle(
        RequestInterface $request,
        MiddlewareInterface $next,
    ): ResponseInterface {
        if (!$this->matches($request->ipAddress)) {
            throw HttpException::fromForbidden();
        }

        return $next->handle($request, $next);
    }

    /**
     * @return list<array{packed: string, prefix: int}>
     *
     * @throws HttpException
     */
    private function resolveEntry(
        string $entry,
    ): array {
        if (\str_contains($entry, '/')) {
            return [
                $this->parseCidr($entry),
            ];
        }

        $packed = \inet_pton($entry);

        if ($packed !== false) {
            return [
                [
                    'packed' => $packed,
                    'prefix' => \strlen($packed) * 8,
                ],
            ];
        }

        return $this->resolveHostname($entry);
    }

    /**
     * @return array{packed: string, prefix: int}
     *
     * @throws HttpException
     */
    private function parseCidr(
        string $cidr,
    ): array {
        $parts = \explode('/', $cidr, 2);
        $packed = \inet_pton($parts[0]);

        if ($packed === false) {
            throw HttpException::fromInternalServerError();
        }

        if (!\ctype_digit($parts[1])) {
            throw HttpException::fromInternalServerError();
        }

        $prefix = (int) $parts[1];

        if ($prefix > \strlen($packed) * 8) {
            throw HttpException::fromInternalServerError();
        }

        return [
            'packed' => $this->applyMask($packed, $prefix),
            'prefix' => $prefix,
        ];
    }

    /**
     * @return list<array{packed: string, prefix: int}>
     *
     * @throws HttpException
     */
    private function resolveHostname(
        string $hostname,
    ): array {
        $ips = \gethostbynamel($hostname);

        if ($ips === false) {
            throw HttpException::fromInternalServerError();
        }

        $resolved = [];

        foreach ($ips as $ip) {
            $packed = \inet_pton($ip);

            if ($packed === false) {
                continue; // @codeCoverageIgnore
            }

            $resolved[] = [
                'packed' => $packed,
                'prefix' => \strlen($packed) * 8,
            ];
        }

        return $resolved;
    }

    private function matches(
        string $ipAddress,
    ): bool {
        $packed = \inet_pton($ipAddress);

        if ($packed === false) {
            return false;
        }

        foreach ($this->resolved as $entry) {
            if (\strlen($entry['packed']) !== \strlen($packed)) {
                continue;
            }

            if ($this->applyMask($packed, $entry['prefix']) === $entry['packed']) {
                return true;
            }
        }

        return false;
    }

    private function applyMask(
        string $packed,
        int $prefix,
    ): string {
        $length = \strlen($packed);
        $fullBytes = \intdiv($prefix, 8);
        $remainderBits = $prefix % 8;
        $result = \substr($packed, 0, $fullBytes);

        if ($remainderBits > 0) {
            $byte = \ord($packed[$fullBytes]);
            $mask = (0xFF << (8 - $remainderBits)) & 0xFF;

            /** @var int<0, 255> $masked */
            $masked = $byte & $mask;
            $result .= \chr($masked);

            $fullBytes++;
        }

        if ($fullBytes < $length) {
            $result .= \str_repeat("\0", $length - $fullBytes);
        }

        return $result;
    }
}
