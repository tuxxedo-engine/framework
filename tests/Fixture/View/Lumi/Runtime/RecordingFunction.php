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

use Tuxxedo\View\Lumi\Library\Function\FunctionInterface;
use Tuxxedo\View\Lumi\Runtime\RuntimeContextInterface;

class RecordingFunction implements FunctionInterface
{
    /**
     * @var mixed[]
     */
    public array $lastArguments = [];

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
        array $arguments,
        \Closure $context,
    ): mixed {
        $this->lastArguments = $arguments;
        $this->lastContext = $context();

        return $this->returnValue;
    }
}
