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

namespace Console\Middleware;

use Tuxxedo\Application\Environment;
use Tuxxedo\Console\ConsoleException;
use Tuxxedo\Console\ExitCode;

class DevelopmentOnlyException extends ConsoleException
{
    /**
     * @param list<string> $commandPath
     */
    public static function fromCommandRejection(
        array $commandPath,
        Environment $currentEnvironment,
    ): self {
        return new self(
            exitCode: ExitCode::NO_PERMISSION,
            message: \sprintf(
                'Command "%s" is marked #[DevelopmentOnly] and cannot run under the %s environment',
                \join(' ', $commandPath),
                $currentEnvironment->name,
            ),
        );
    }
}
