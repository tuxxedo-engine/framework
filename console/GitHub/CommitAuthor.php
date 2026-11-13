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

namespace Console\GitHub;

use Tuxxedo\Temporal\InstantInterface;

readonly class CommitAuthor
{
    public function __construct(
        public string $name,
        public InstantInterface $date,
    ) {
    }
}
