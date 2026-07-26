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

namespace Tuxxedo\File\Storage\Local\Config;

use Tuxxedo\Container\DefaultImplementation;
use Tuxxedo\Container\Lifecycle;

#[DefaultImplementation(class: LocalStorageConfig::class, lifecycle: Lifecycle::SINGLETON)]
interface LocalStorageConfigInterface
{
    public string $root {
        get;
    }

    public bool $autoCreateDirectories {
        get;
    }

    public bool $allowCaseInsensitiveFilesystem {
        get;
    }
}
