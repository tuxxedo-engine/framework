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

use Tuxxedo\Serializer\Attribute\ListOf;
use Tuxxedo\Serializer\Attribute\MapOf;
use Tuxxedo\Serializer\Attribute\XmlAttribute;
use Tuxxedo\Serializer\Attribute\XmlList;
use Tuxxedo\Serializer\Attribute\XmlRoot;

#[XmlRoot('order')]
class XmlOrder
{
    /**
     * @param list<XmlLine> $lines
     * @param list<string> $tags
     * @param array<string, int> $stock
     */
    public function __construct(
        #[XmlAttribute]
        public readonly int $id,
        public readonly string $customer,
        public readonly XmlMoney $total,
        #[ListOf(XmlLine::class), XmlList(item: 'line')]
        public readonly array $lines = [],
        #[ListOf('string'), XmlList(item: 'tag', wrapped: false)]
        public readonly array $tags = [],
        #[MapOf('int')]
        public readonly array $stock = [],
        public readonly ?string $note = null,
        public readonly bool $paid = false,
        public readonly Status $status = Status::ACTIVE,
    ) {
    }
}
