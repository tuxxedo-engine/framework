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

namespace Tuxxedo\Model\MetaData;

class ModelRelationLink implements ModelRelationLinkInterface
{
    /**
     * @param list<array{string, string}> $throughOn
     * @param list<array{string, string}> $parentOn
     * @param list<array{string, string}> $filters
     */
    public function __construct(
        public readonly ModelMetaDataInterface $target,
        public readonly array $parentOn,
        public readonly ?string $throughTable = null,
        public readonly ?ModelMetaDataInterface $throughMetaData = null,
        public readonly array $throughOn = [],
        public readonly array $filters = [],
    ) {
    }
}
