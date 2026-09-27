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
use Tuxxedo\Console\Attribute\Option;
use Tuxxedo\Console\ExitCode;

class ConflictingParamCommand
{
    #[Command('demo:conflict-param')]
    public function run(
        #[Argument]
        #[Option]
        string $value,
    ): ExitCode {
        return ExitCode::SUCCESS;
    }
}
