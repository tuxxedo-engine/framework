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

namespace Fixture\Serializer;

use Tuxxedo\Serializer\Attribute\CaseStyle;
use Tuxxedo\Serializer\Attribute\Ignore;
use Tuxxedo\Serializer\Attribute\ListOf;
use Tuxxedo\Serializer\Attribute\MapOf;
use Tuxxedo\Serializer\Attribute\Name;
use Tuxxedo\Serializer\KeyCase;

#[CaseStyle(KeyCase::SNAKE)]
class Customer
{
    #[Ignore]
    public string $secret = 'hidden';

    /**
     * @param list<Address> $addresses
     * @param list<int> $tagIds
     * @param array<string, float> $prices
     */
    public function __construct(
        public readonly int $id,
        public readonly string $firstName,
        public readonly ?string $nickname,
        #[Name('e-mail')]
        public readonly string $email,
        public readonly bool $active = true,
        public readonly float $balance = 0.0,
        public readonly Status $status = Status::ACTIVE,
        public readonly Priority $priority = Priority::LOW,
        public readonly Level $level = Level::BEGINNER,
        public readonly ?Address $billingAddress = null,
        #[ListOf(Address::class)]
        public readonly array $addresses = [],
        #[ListOf('int')]
        public readonly array $tagIds = [],
        #[MapOf('float')]
        public readonly array $prices = [],
    ) {
    }
}
