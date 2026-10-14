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

use Tuxxedo\Intl\Locale;

abstract class AbstractMemoizedLocaleSource implements LocaleSourceInterface
{
    private bool $detected = false;
    private ?Locale $detectedLocale = null;

    public function detect(): ?Locale
    {
        if (!$this->detected) {
            $this->detectedLocale = $this->resolve();
            $this->detected = true;
        }

        return $this->detectedLocale;
    }

    abstract protected function resolve(): ?Locale;
}
