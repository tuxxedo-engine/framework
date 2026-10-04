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

interface RuntimeIntrospectorInterface
{
    public function hasFunction(
        string $name,
    ): bool;

    public function getFunction(
        string $name,
    ): ?CallableMetadataInterface;

    public function hasFilter(
        string $name,
    ): bool;

    public function getFilter(
        string $name,
    ): ?CallableMetadataInterface;
}
