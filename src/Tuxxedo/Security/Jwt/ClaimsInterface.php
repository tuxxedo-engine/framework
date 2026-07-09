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

interface ClaimsInterface
{
    public ?string $issuer {
        get;
    }

    public ?string $subject {
        get;
    }

    /**
     * @var list<string>|null
     */
    public ?array $audience {
        get;
    }

    public ?\DateTimeImmutable $expiresAt {
        get;
    }

    public ?\DateTimeImmutable $notBefore {
        get;
    }

    public ?\DateTimeImmutable $issuedAt {
        get;
    }

    public ?string $id {
        get;
    }

    /**
     * @var array<string, mixed>
     */
    public array $all {
        get;
    }

    public function has(
        string $claim,
    ): bool;

    public function get(
        string $claim,
    ): mixed;
}
