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

interface ModelRelationLinkInterface
{
    public ModelMetaDataInterface $target {
        get;
    }

    public ?string $throughTable {
        get;
    }

    public ?ModelMetaDataInterface $throughMetaData {
        get;
    }

    /**
     * @var list<array{string, string}>
     */
    public array $throughOn {
        get;
    }

    /**
     * @var list<array{string, string}>
     */
    public array $parentOn {
        get;
    }

    /**
     * @var list<array{string, string}>
     */
    public array $filters {
        get;
    }
}
