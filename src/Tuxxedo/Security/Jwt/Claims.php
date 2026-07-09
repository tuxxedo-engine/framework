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

namespace Tuxxedo\Security\Jwt;

class Claims implements ClaimsInterface
{
    /**
     * @var array<string, mixed>
     */
    public readonly array $all;

    public ?string $issuer {
        get {
            $iss = $this->all['iss'] ?? null;

            return \is_string($iss)
                ? $iss
                : null;
        }
    }

    public ?string $subject {
        get {
            $sub = $this->all['sub'] ?? null;

            return \is_string($sub)
                ? $sub
                : null;
        }
    }

    public ?array $audience {
        get {
            $aud = $this->all['aud'] ?? null;

            if (\is_string($aud)) {
                return [$aud];
            }

            if (!\is_array($aud)) {
                return null;
            }

            /** @var list<string> $normalized */
            $normalized = \array_values($aud);

            return $normalized;
        }
    }

    public ?\DateTimeImmutable $expiresAt {
        get {
            return $this->timestampClaim(
                claim: 'exp',
            );
        }
    }

    public ?\DateTimeImmutable $notBefore {
        get {
            return $this->timestampClaim(
                claim: 'nbf',
            );
        }
    }

    public ?\DateTimeImmutable $issuedAt {
        get {
            return $this->timestampClaim(
                claim: 'iat',
            );
        }
    }

    public ?string $id {
        get {
            $jti = $this->all['jti'] ?? null;

            return \is_string($jti)
                ? $jti
                : null;
        }
    }

    /**
     * @param array<string, mixed> $all
     */
    public function __construct(
        array $all,
    ) {
        $this->all = $all;
    }

    public function has(
        string $claim,
    ): bool {
        return \array_key_exists($claim, $this->all);
    }

    public function get(
        string $claim,
    ): mixed {
        return $this->all[$claim] ?? null;
    }

    private function timestampClaim(
        string $claim,
    ): ?\DateTimeImmutable {
        $value = $this->all[$claim] ?? null;

        if (!\is_int($value) && !\is_float($value)) {
            return null;
        }

        $result = \DateTimeImmutable::createFromFormat(
            format: 'U.u',
            datetime: \sprintf('%.6f', $value),
            timezone: new \DateTimeZone('UTC'),
        );

        if ($result === false) {
            return null;
        }

        return $result;
    }
}
