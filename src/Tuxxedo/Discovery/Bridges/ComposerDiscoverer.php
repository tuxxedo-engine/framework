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

namespace Tuxxedo\Discovery\Bridges;

use Composer\InstalledVersions;
use Tuxxedo\Discovery\DiscoveryChannelInterface;
use Tuxxedo\Discovery\DiscoveryType;

class ComposerDiscoverer implements DiscoveryChannelInterface
{
    private const string COMPOSER_INDEX = 'tuxxedo-engine';

    /**
     * @var array<string, array<class-string>>
     */
    private array $packages;

    private function lazyLoad(): void
    {
        if (isset($this->packages)) {
            return;
        }

        $this->packages = [];

        foreach (DiscoveryType::cases() as $type) {
            $this->packages[$type->name] = [];
        }

        foreach (InstalledVersions::getInstalledPackages() as $package) {
            $path = InstalledVersions::getInstallPath($package);

            if ($path === null) {
                continue;
            }

            $json = @\file_get_contents($path . '/composer.json');

            if (!\is_string($json)) {
                continue;
            }

            $json = \json_decode($json);

            if (
                !\is_array($json) ||
                !\array_key_exists('extra', $json) ||
                !\is_array($json['extra']) ||
                !\array_key_exists(self::COMPOSER_INDEX, $json['extra']) ||
                !\is_array($json['extra'][self::COMPOSER_INDEX])
            ) {
                continue;
            }

            foreach ($this->provides() as $type) {
                $typeIndex = \strtolower($type->name);

                if (
                    !\array_key_exists($typeIndex, $json['extra'][self::COMPOSER_INDEX]) ||
                    !\is_array($json['extra'][self::COMPOSER_INDEX][$typeIndex])
                ) {
                    continue;
                }

                foreach ($json['extra'][self::COMPOSER_INDEX][$typeIndex] as $discoveredClass) {
                    if (
                        \is_string($discoveredClass) &&
                        \class_exists($discoveredClass) &&
                        $type->isValidSubClass($discoveredClass)
                    ) {
                        $this->packages[$type->name][] = $discoveredClass;
                    }
                }
            }
        }
    }

    public function provides(): array
    {
        return [
            DiscoveryType::EXTENSIONS,
            DiscoveryType::MIDDLEWARE,
            DiscoveryType::SERVICES,
        ];
    }

    public function discover(DiscoveryType $type): array
    {
        $this->lazyLoad();

        return $this->packages[$type->name];
    }
}
