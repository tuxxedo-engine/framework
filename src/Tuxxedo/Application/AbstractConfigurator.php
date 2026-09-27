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

namespace Tuxxedo\Application;

use Tuxxedo\Config\ConfigInterface;
use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Database\Config\ConnectionManagerConfigInterface;
use Tuxxedo\Database\ConnectionManager;
use Tuxxedo\Database\ConnectionManagerInterface;
use Tuxxedo\File\Storage\Local\Config\LocalStorageConfigInterface;
use Tuxxedo\File\Storage\Local\LocalStorage;
use Tuxxedo\File\Storage\StorageInterface;
use Tuxxedo\Mail\MailConfigurator;
use Tuxxedo\Mail\MailConfiguratorInterface;
use Tuxxedo\Mail\MailManagerInterface;
use Tuxxedo\View\Lumi\LumiConfigurator;
use Tuxxedo\View\Lumi\LumiConfiguratorInterface;
use Tuxxedo\View\Lumi\LumiViewRenderInterface;
use Tuxxedo\View\ViewRenderInterface;

abstract class AbstractConfigurator implements AbstractConfiguratorInterface
{
    public private(set) ?LumiConfiguratorInterface $lumiConfigurator = null;
    public private(set) bool $useDefaultLumi = false;
    public private(set) ?\Closure $lumiCustomizer = null;
    public private(set) ?ConnectionManagerInterface $connectionManager = null;
    public private(set) bool $useDefaultConnectionManager = false;
    public private(set) ?\Closure $connectionManagerCustomizer = null;
    public private(set) ?StorageInterface $storage = null;
    public private(set) bool $useDefaultStorage = false;
    public private(set) ?MailManagerInterface $mailManager = null;
    public private(set) bool $useDefaultMailManager = false;
    public private(set) ?\Closure $mailManagerCustomizer = null;

    /**
     * @var list<string>
     */
    public private(set) array $serviceFiles = [];

    public function __construct(
        public private(set) ?ConfigInterface $config = null,
        public private(set) ?ContainerInterface $container = null,
    ) {
    }

    public function withLumi(
        LumiConfiguratorInterface $lumiConfigurator,
    ): self {
        $this->lumiConfigurator = $lumiConfigurator;
        $this->useDefaultLumi = false;
        $this->lumiCustomizer = null;

        return $this;
    }

    /**
     * @param (\Closure(LumiConfiguratorInterface $configurator): mixed)|null $customizer
     */
    public function withDefaultLumi(
        ?\Closure $customizer = null,
    ): self {
        $this->useDefaultLumi = true;
        $this->lumiCustomizer = $customizer;
        $this->lumiConfigurator = null;

        return $this;
    }

    public function withConnectionManager(
        ConnectionManagerInterface $connectionManager,
    ): self {
        $this->connectionManager = $connectionManager;
        $this->useDefaultConnectionManager = false;
        $this->connectionManagerCustomizer = null;

        return $this;
    }

    /**
     * @param (\Closure(ConnectionManagerInterface $manager): mixed)|null $customizer
     */
    public function withDefaultConnectionManager(
        ?\Closure $customizer = null,
    ): self {
        $this->useDefaultConnectionManager = true;
        $this->connectionManagerCustomizer = $customizer;
        $this->connectionManager = null;

        return $this;
    }

    public function withStorage(
        StorageInterface $storage,
    ): self {
        $this->storage = $storage;
        $this->useDefaultStorage = false;

        return $this;
    }

    public function withDefaultStorage(): self
    {
        $this->useDefaultStorage = true;
        $this->storage = null;

        return $this;
    }

    public function withMailManager(
        MailManagerInterface $mailManager,
    ): self {
        $this->mailManager = $mailManager;
        $this->useDefaultMailManager = false;
        $this->mailManagerCustomizer = null;

        return $this;
    }

    /**
     * @param (\Closure(MailConfiguratorInterface $configurator): mixed)|null $customizer
     */
    public function withDefaultMailManager(
        ?\Closure $customizer = null,
    ): self {
        $this->useDefaultMailManager = true;
        $this->mailManagerCustomizer = $customizer;
        $this->mailManager = null;

        return $this;
    }

    public function withServiceFile(
        string $file,
    ): self {
        $this->serviceFiles[] = $file;

        return $this;
    }

    public function withoutServiceFiles(): self
    {
        $this->serviceFiles = [];

        return $this;
    }

    protected function registerLumi(
        ContainerInterface $container,
    ): void {
        if ($this->lumiConfigurator !== null) {
            $lumiConfigurator = $this->lumiConfigurator;

            $container->singletonLazy(
                LumiViewRenderInterface::class,
                static fn (): LumiViewRenderInterface => $lumiConfigurator->build(),
            );

            $container->alias(
                ViewRenderInterface::class,
                LumiViewRenderInterface::class,
            );

            return;
        }

        if (!$this->useDefaultLumi) {
            return;
        }

        $customizer = $this->lumiCustomizer;

        $container->singletonLazy(
            LumiViewRenderInterface::class,
            static function (ContainerInterface $container) use ($customizer): LumiViewRenderInterface {
                $lumi = LumiConfigurator::fromConfig($container);

                if ($customizer !== null) {
                    $customizer($lumi);
                }

                return $lumi->build();
            },
        );

        $container->alias(
            ViewRenderInterface::class,
            LumiViewRenderInterface::class,
        );
    }

    protected function registerConnectionManager(
        ContainerInterface $container,
    ): void {
        if ($this->connectionManager !== null) {
            $connectionManager = $this->connectionManager;

            $container->singletonLazy(
                ConnectionManagerInterface::class,
                static fn (): ConnectionManagerInterface => $connectionManager,
            );

            return;
        }

        if (!$this->useDefaultConnectionManager) {
            return;
        }

        $customizer = $this->connectionManagerCustomizer;

        $container->singletonLazy(
            ConnectionManagerInterface::class,
            static function (ContainerInterface $container) use ($customizer): ConnectionManagerInterface {
                $manager = ConnectionManager::createFromConfig(
                    container: $container,
                    config: $container->resolve(ConnectionManagerConfigInterface::class),
                );

                if ($customizer !== null) {
                    $customizer($manager);
                }

                return $manager;
            },
        );
    }

    protected function registerStorage(
        ContainerInterface $container,
    ): void {
        if ($this->storage !== null) {
            $storage = $this->storage;

            $container->singletonLazy(
                StorageInterface::class,
                static fn (): StorageInterface => $storage,
            );

            return;
        }

        if (!$this->useDefaultStorage) {
            return;
        }

        $container->singletonLazy(
            StorageInterface::class,
            static fn (ContainerInterface $container): StorageInterface => new LocalStorage(
                config: $container->resolve(LocalStorageConfigInterface::class),
            ),
        );
    }

    protected function registerMailManager(
        ContainerInterface $container,
    ): void {
        if ($this->mailManager !== null) {
            $mailManager = $this->mailManager;

            $container->singletonLazy(
                MailManagerInterface::class,
                static fn (): MailManagerInterface => $mailManager,
            );

            return;
        }

        if (!$this->useDefaultMailManager) {
            return;
        }

        $customizer = $this->mailManagerCustomizer;

        $container->singletonLazy(
            MailManagerInterface::class,
            static function (ContainerInterface $container) use ($customizer): MailManagerInterface {
                $configurator = MailConfigurator::fromConfig($container);

                if ($customizer !== null) {
                    $customizer($configurator);
                }

                return $configurator->build();
            },
        );
    }

    protected function loadServiceFiles(
        ContainerInterface $container,
    ): void {
        foreach ($this->serviceFiles as $serviceFile) {
            $container->callFile($serviceFile);
        }
    }
}
