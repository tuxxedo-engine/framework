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

readonly class Commit
{
    public function __construct(
        public string $sha,
        public CommitDetails $commit,
        public ?GitHubUser $author = null,
        public ?CommitStats $stats = null,
    ) {
    }
}
