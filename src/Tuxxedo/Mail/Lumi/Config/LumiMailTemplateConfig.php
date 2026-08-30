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

namespace Tuxxedo\Mail\Lumi\Config;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Mail\Config\MailTemplateConfigInterface;
use Tuxxedo\Mail\Lumi\LumiMailTemplateRender;
use Tuxxedo\Mail\MailTemplateRenderInterface;
use Tuxxedo\View\Lumi\LumiConfigurator;

readonly class LumiMailTemplateConfig implements MailTemplateConfigInterface
{
    public function __construct(
        public string $directory,
        public string $cacheDirectory,
        public string $extension = '.lumi',
        public bool $alwaysCompile = false,
        public bool $disableErrorReporting = true,
    ) {
    }

    public function createTemplateRender(
        ContainerInterface $container,
    ): MailTemplateRenderInterface {
        $configurator = new LumiConfigurator(
            container: $container,
        );

        $configurator->viewDirectory(
            directory: $this->directory,
        );

        $configurator->cacheDirectory(
            directory: $this->cacheDirectory,
        );

        if ($this->extension !== '') {
            $configurator->viewExtension(
                extension: $this->extension,
            );
        }

        if ($this->alwaysCompile) {
            $configurator->enableAlwaysCompile();
        } else {
            $configurator->disableAlwaysCompile();
        }

        if ($this->disableErrorReporting) {
            $configurator->disableErrorReporting();
        } else {
            $configurator->enableErrorReporting();
        }

        return new LumiMailTemplateRender(
            viewRender: $configurator->build(),
        );
    }
}
