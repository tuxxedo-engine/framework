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

class Header implements HeaderInterface
{
    /**
     * @var array<string, mixed>
     */
    public readonly array $all;

    public string $algorithm {
        get {
            /** @var non-empty-string $alg */
            $alg = $this->all['alg'];

            return $alg;
        }
    }

    public ?string $type {
        get {
            $typ = $this->all['typ'] ?? null;

            return \is_string($typ)
                ? $typ
                : null;
        }
    }

    public ?string $keyId {
        get {
            $kid = $this->all['kid'] ?? null;

            return \is_string($kid)
                ? $kid
                : null;
        }
    }

    /**
     * @param array<string, mixed> $all
     *
     * @throws JwtException
     */
    public function __construct(
        array $all,
    ) {
        if (!\array_key_exists('alg', $all)) {
            throw JwtException::fromMissingHeader(
                header: 'alg',
            );
        }

        if (!\is_string($all['alg']) || $all['alg'] === '') {
            throw JwtException::fromInvalidHeaderValue(
                header: 'alg',
            );
        }

        $this->all = $all;
    }

    public function has(
        string $header,
    ): bool {
        return \array_key_exists($header, $this->all);
    }

    public function get(
        string $header,
    ): mixed {
        return $this->all[$header] ?? null;
    }
}
