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

use Tuxxedo\Config\Attribute\ConfigNamespace;

#[ConfigNamespace('storage.local')]
readonly class LocalStorageConfig implements LocalStorageConfigInterface
{
    public function __construct(
        public string $root,
        public bool $autoCreateDirectories = true,
        public bool $allowCaseInsensitiveFilesystem = false,
    ) {
    }
}
