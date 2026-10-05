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

namespace Tuxxedo\View\Lumi\Runtime\Introspector;

class CallableRegistry implements CallableRegistryInterface
{
    /**
     * @var list<CallableMetadataInterface>
     */
    public private(set) array $functions = [];

    /**
     * @var list<CallableMetadataInterface>
     */
    public private(set) array $filters = [];

    public function registerFunction(
        CallableMetadataInterface $metadata,
    ): void {
        $this->functions[] = $metadata;
    }

    public function registerFilter(
        CallableMetadataInterface $metadata,
    ): void {
        $this->filters[] = $metadata;
    }
}
