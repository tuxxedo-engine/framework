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

use Tuxxedo\View\Lumi\Library\Attribute\Context;
use Tuxxedo\View\Lumi\Library\Attribute\LumiFunction;
use Tuxxedo\View\Lumi\Runtime\RuntimeContextInterface;

class RecordingFunction
{
    /**
     * @var mixed[]
     */
    public array $lastArguments = [];

    public ?RuntimeContextInterface $lastContext = null;
    public mixed $returnValue = null;

    #[LumiFunction('recording', aliases: ['recorder'])]
    public function run(
        #[Context]
        RuntimeContextInterface $context,
        mixed ...$arguments,
    ): mixed {
        $this->lastContext = $context;
        $this->lastArguments = $arguments;

        return $this->returnValue;
    }
}
