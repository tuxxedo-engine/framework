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

namespace Tuxxedo\Debug\Config;

readonly class DebugConfig implements DebugConfigInterface
{
    public function __construct(
        public bool $alwaysShow,
        public string $rootPath,
        public bool $registerPhpErrorHandler,
        public ?DebugEditor $openInEditor = null,
        public ?string $editorRemotePath = null,
        public ?string $editorLocalPath = null,
    ) {
    }
}
