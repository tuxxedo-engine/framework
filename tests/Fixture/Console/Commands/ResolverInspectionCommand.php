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

use Tuxxedo\Application\Environment;
use Tuxxedo\Console\Attribute\Command;
use Tuxxedo\Console\ExitCode;
use Tuxxedo\Console\Kernel\KernelInterface;
use Tuxxedo\Console\Kernel\Resolver\App;
use Tuxxedo\Console\Kernel\Resolver\AppEnvironment;
use Tuxxedo\Console\Kernel\Resolver\AppName;
use Tuxxedo\Console\Kernel\Resolver\AppVersion;

class ResolverInspectionCommand
{
    public static ?KernelInterface $observedKernel = null;
    public static ?string $observedName = null;
    public static ?string $observedVersion = null;
    public static ?Environment $observedEnvironment = null;

    public function __construct(
        #[App]
        private readonly KernelInterface $app,
        #[AppName]
        private readonly string $name,
        #[AppVersion]
        private readonly string $version,
        #[AppEnvironment]
        private readonly Environment $environment,
    ) {
    }

    public static function reset(): void
    {
        self::$observedKernel = null;
        self::$observedName = null;
        self::$observedVersion = null;
        self::$observedEnvironment = null;
    }

    #[Command('demo:resolver-inspect')]
    public function run(): ExitCode
    {
        self::$observedKernel = $this->app;
        self::$observedName = $this->name;
        self::$observedVersion = $this->version;
        self::$observedEnvironment = $this->environment;

        return ExitCode::SUCCESS;
    }
}
