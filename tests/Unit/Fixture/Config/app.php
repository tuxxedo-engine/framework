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

use Unit\Fixture\Config\AppInfoRegistry;

return static function (AppInfoRegistry $registry): array {
    return [
        'name' => $registry->appName,
        'version' => $registry->appVersion,
    ];
};
