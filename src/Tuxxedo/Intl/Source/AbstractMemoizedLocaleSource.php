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

namespace Tuxxedo\Intl\Source;

use Tuxxedo\Intl\LocaleInterface;

abstract class AbstractMemoizedLocaleSource implements LocaleSourceInterface
{
    private bool $detected = false;
    private ?LocaleInterface $detectedLocale = null;

    public function detect(): ?LocaleInterface
    {
        if (!$this->detected) {
            $this->detectedLocale = $this->resolve();
            $this->detected = true;
        }

        return $this->detectedLocale;
    }

    abstract protected function resolve(): ?LocaleInterface;
}
