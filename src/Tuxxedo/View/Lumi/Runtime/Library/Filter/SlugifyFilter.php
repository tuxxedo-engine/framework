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

namespace Tuxxedo\View\Lumi\Runtime\Library\Filter;

use Tuxxedo\View\Lumi\Library\Filter\FilterInterface;
use Tuxxedo\View\Lumi\Runtime\RuntimeContextInterface;

class SlugifyFilter implements FilterInterface
{
    public private(set) string $name = 'slugify';
    public private(set) array $aliases = [];

    /**
     * @param \Closure(): RuntimeContextInterface $context
     */
    public function call(
        mixed $value,
        \Closure $context,
    ): string {
        /** @var string $value */

        return \mb_trim(
            \preg_replace(
                '/[^\p{L}\p{Nd}]+/u',
                '-',
                \mb_strtolower(
                    $value,
                ),
            ) ?? '',
        );
    }
}
