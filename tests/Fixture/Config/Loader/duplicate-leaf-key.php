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

use Fixture\Config\Typed\DuplicateLeafKeyConfig;
use Fixture\Config\Typed\DuplicateLeafKeyConfigInterface;

return static fn (): DuplicateLeafKeyConfigInterface => new DuplicateLeafKeyConfig(
    shared: 'first',
    other: 'second',
);
