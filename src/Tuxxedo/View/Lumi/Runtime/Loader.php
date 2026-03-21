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

readonly class Loader implements LoaderInterface
{
    public function __construct(
        public string $directory,
        public string $cacheDirectory,
        public string $extension,
    ) {
    }

    public function getViewFileName(
        string $view,
    ): string {
        return $this->directory . '/' . $view . $this->extension;
    }

    public function getCachedFileName(
        string $view,
    ): string {
        return $this->cacheDirectory . '/' . $view . '.php';
    }

    public function exists(
        string $view,
    ): bool {
        return \is_file($this->getViewFileName($view));
    }

    public function isCached(
        string $view,
    ): bool {
        return \is_file($this->getCachedFileName($view));
    }

    public function invalidate(
        string $view,
    ): bool {
        if (!$this->isCached($view)) {
            return true;
        }

        return @\unlink($this->getCachedFileName($view));
    }
}
