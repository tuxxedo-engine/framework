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

namespace Tuxxedo\Console;

use Tuxxedo\Application\Profile;
use Tuxxedo\Application\ServiceProviderInterface;
use Tuxxedo\Config\Config;
use Tuxxedo\Config\ConfigInterface;
use Tuxxedo\Container\Container;
use Tuxxedo\Container\ContainerInterface;

class Console implements ConsoleApplicationInterface
{
    public readonly ConfigInterface $config;
    public readonly ContainerInterface $container;

    final public function __construct(
        public readonly string $appName = '',
        public readonly string $appVersion = '',
        public readonly Profile $appProfile = Profile::RELEASE,
        ?ContainerInterface $container = null,
        ?Config $config = null,
    ) {
        $this->config = $config ?? new Config();
        $this->container = $container ?? new Container();

        $this->container->bind($this);
        $this->container->bind($this->container);
    }

    public static function createFromDirectory(
        string $directory,
    ): static {
        $config = Config::createFromDirectory($directory . '/config');

        return new static(
            appName: $config->getString('app.name'),
            appVersion: $config->getString('app.version'),
            appProfile: $config->getEnum('app.profile', Profile::class),
            config: $config,
        );
    }

    /**
     * @param ServiceProviderInterface|(\Closure(): ServiceProviderInterface) $provider
     */
    public function serviceProvider(
        ServiceProviderInterface|\Closure $provider,
    ): static {
        if ($provider instanceof \Closure) {
            $provider = $provider();
        }

        $provider->load($this->container);

        return $this;
    }
}
