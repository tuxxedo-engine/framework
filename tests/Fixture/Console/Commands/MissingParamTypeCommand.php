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

namespace Fixture\Console\Commands;

use Tuxxedo\Console\Attribute\Argument;
use Tuxxedo\Console\Attribute\Command;
use Tuxxedo\Console\ExitCode;

class MissingParamTypeCommand
{
    /**
     * @param mixed $value
     */
    #[Command('demo:missing-param-type')]
    public function run(
        #[Argument]
        $value,
    ): ExitCode {
        return ExitCode::SUCCESS;
    }
}
