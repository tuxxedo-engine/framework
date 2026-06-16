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

namespace Tuxxedo\Net;

readonly class IpRangeList implements IpRangeListInterface
{
    /**
     * @var list<array{packed: string, prefix: int}>
     */
    private array $resolved;

    /**
     * @param string[] $entries
     *
     * @throws NetException
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

    public function contains(
        string $ip,
    ): bool {
        $packed = \inet_pton($ip);

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

    /**
     * @return list<array{packed: string, prefix: int}>
     *
     * @throws NetException
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
     * @throws NetException
     */
    private function parseCidr(
        string $cidr,
    ): array {
        $parts = \explode('/', $cidr, 2);
        $packed = \inet_pton($parts[0]);

        if ($packed === false) {
            throw NetException::fromUnparseableCidrAddress(
                cidr: $cidr,
            );
        }

        if (!$this->isPositiveInteger($parts[1])) {
            throw NetException::fromNonNumericCidrPrefix(
                cidr: $cidr,
            );
        }

        $prefix = (int) $parts[1];

        if ($prefix > \strlen($packed) * 8) {
            throw NetException::fromCidrPrefixOutOfRange(
                cidr: $cidr,
            );
        }

        return [
            'packed' => $this->applyMask($packed, $prefix),
            'prefix' => $prefix,
        ];
    }

    private function isPositiveInteger(
        string $value,
    ): bool {
        return $value !== '' && \strspn($value, '0123456789') === \strlen($value);
    }

    /**
     * @return list<array{packed: string, prefix: int}>
     *
     * @throws NetException
     */
    private function resolveHostname(
        string $hostname,
    ): array {
        $ips = \gethostbynamel($hostname);

        if ($ips === false) {
            throw NetException::fromUnresolvableHostname(
                hostname: $hostname,
            );
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
