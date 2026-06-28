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

use Fixture\Config\Typed\SimpleConfig;
use Fixture\Config\Typed\SimpleConfigInterface;

return static fn (): SimpleConfigInterface => new SimpleConfig(
    name: 'fixture',
    count: 7,
);
