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

namespace Tuxxedo\View\Lumi\Runtime\Library\Filter;

use Tuxxedo\Escaper\Escaper;
use Tuxxedo\Escaper\EscaperInterface;
use Tuxxedo\View\Lumi\Runtime\Directive\DirectivesInterface;
use Tuxxedo\View\Lumi\Runtime\Filter\FilterInterface;

class EscapeJsFilter implements FilterInterface
{
    public private(set) string $name = 'escape_js';
    public private(set) array $aliases = [];

    private readonly EscaperInterface $escaper;

    public function __construct(
        ?EscaperInterface $escaper = null,
    ) {
        $this->escaper = $escaper ?? new Escaper();
    }

    public function call(
        mixed $value,
        DirectivesInterface $directives,
    ): string {
        /** @var string $value */

        return $this->escaper->js(\strval($value));
    }
}
