<?php

/**
 * Tuxxedo Engine
 *
 * This file is part of the Tuxxedo Engine framework and is licensed under
 * the MIT license.
 *
 * Copyright (C) 2025 Kalle Sommer Nielsen <kalle@php.net>
 */

declare(strict_types=1);

namespace Tuxxedo\View\Lumi\Runtime;

interface LumiLoaderInterface
{
    public function getViewFileName(
        string $view,
    ): string;

    public function getCachedFileName(
        string $view,
    ): string;

    public function exists(
        string $view,
    ): bool;

    public function isCached(
        string $view,
    ): bool;

    public function invalidate(
        string $view,
    ): bool;
}
