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

namespace Fixture\Intl;

use Tuxxedo\Intl\LocaleInterface;
use Tuxxedo\Intl\Source\LocaleSourceInterface;

class StubLocaleSource implements LocaleSourceInterface
{
    public function __construct(
        private readonly ?LocaleInterface $locale,
    ) {
    }

    public function detect(): ?LocaleInterface
    {
        return $this->locale;
    }
}
