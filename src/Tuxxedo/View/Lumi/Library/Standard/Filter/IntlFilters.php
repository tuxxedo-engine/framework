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

namespace Tuxxedo\View\Lumi\Library\Standard\Filter;

use Tuxxedo\Intl\TranslatorInterface;
use Tuxxedo\View\Lumi\Library\Attribute\LumiFilter;

class IntlFilters
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[LumiFilter('translate', aliases: ['t'])]
    public function translate(
        string $value,
    ): string {
        return $this->translator->translate(
            key: $value,
        );
    }
}
