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

namespace Fixture\View\Lumi\Runtime;

use Tuxxedo\View\Lumi\Library\Filter\FilterInterface;
use Tuxxedo\View\Lumi\Runtime\RuntimeContextInterface;

class RecordingFilter implements FilterInterface
{
    public mixed $lastValue = null;
    public ?RuntimeContextInterface $lastContext = null;

    /**
     * @param string[] $aliases
     */
    public function __construct(
        public string $name = 'noop',
        public array $aliases = [],
        public mixed $returnValue = null,
    ) {
    }

    public function call(
        mixed $value,
        \Closure $context,
    ): mixed {
        $this->lastValue = $value;
        $this->lastContext = $context();

        return $this->returnValue ?? $value;
    }
}
