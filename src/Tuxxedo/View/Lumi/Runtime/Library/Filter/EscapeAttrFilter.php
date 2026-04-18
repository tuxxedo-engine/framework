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

use Tuxxedo\Escaper\Escaper;
use Tuxxedo\Escaper\EscaperInterface;
use Tuxxedo\View\Lumi\Library\Filter\FilterInterface;
use Tuxxedo\View\Lumi\Runtime\RuntimeFrameInterface;

class EscapeAttrFilter implements FilterInterface
{
    public private(set) string $name = 'escape_attr';
    public private(set) array $aliases = [];

    public function __construct(
        private readonly EscaperInterface $escaper = new Escaper(),
    ) {
    }

    /**
     * @param \Closure(): RuntimeFrameInterface $frame
     */
    public function call(
        mixed $value,
        \Closure $frame,
    ): string {
        /** @var string $value */

        return $this->escaper->attribute(\strval($value));
    }
}
