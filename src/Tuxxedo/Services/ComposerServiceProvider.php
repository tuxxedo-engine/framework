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

namespace Tuxxedo\Services;

use Composer\InstalledVersions;
use Tuxxedo\Container\Container;

class ComposerServiceProvider implements ServiceProviderInterface
{
    private const string COMPOSER_INDEX = 'tuxxedo-engine';

    public function load(Container $container): void
    {
        foreach (InstalledVersions::getInstalledPackages() as $package) {
            $json = \file_get_contents(InstalledVersions::getInstallPath($package) . '/composer.json');

            if (!\is_string($json)) {
                continue;
            }

            $composerJson = @\json_decode($json, true);

            if (
                !\is_array($composerJson) ||
                !\array_key_exists('extra', $composerJson) ||
                !\is_array($composerJson['extra']) ||
                !\array_key_exists(self::COMPOSER_INDEX, $composerJson['extra']) ||
                !\is_array($composerJson['extra'][self::COMPOSER_INDEX]) ||
                !\array_key_exists('providers', $composerJson['extra'][self::COMPOSER_INDEX]) ||
                !\is_array($composerJson['extra'][self::COMPOSER_INDEX]['providers'])
            ) {
                continue;
            }

            foreach ($composerJson['extra'][self::COMPOSER_INDEX]['providers'] as $providerClass) {
                $provider = new $providerClass();

                if (!$provider instanceof ServiceProviderInterface) {
                    continue;
                }

                $container->persistent($provider);
            }
        }
    }
}
