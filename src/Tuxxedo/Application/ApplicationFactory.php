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

namespace Tuxxedo\Application;

use Tuxxedo\Config\Config;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Http\Kernel\Kernel;

class ApplicationFactory
{
    final private function __construct()
    {
    }

    public static function createFromDirectory(
        string $directory,
    ): Kernel {
        $config = Config::createFromDirectory($directory . '/config');

        $kernel = new Kernel(
            appName: $config->getString('app.name'),
            appVersion: $config->getString('app.version'),
            appProfile: $config->getEnum('app.profile', Profile::class),
            config: $config,
        );

        if (\is_file($directory . '/services.php')) {
            $kernel->serviceProvider(
                new readonly class ($directory . '/services.php') implements ServiceProviderInterface {
                    public function __construct(
                        public string $file,
                    ) {
                    }

                    public function load(ContainerInterface $container): void
                    {
                        $provider = (static fn (string $file): mixed => require $file)($this->file);

                        if ($provider instanceof \Closure) {
                            $provider($container);
                        }
                    }
                },
            );
        }

        return $kernel;
    }
}
