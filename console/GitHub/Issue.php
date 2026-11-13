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

use Tuxxedo\Serializer\Attribute\CaseStyle;
use Tuxxedo\Temporal\InstantInterface;
use Tuxxedo\Text\KeyCase;

#[CaseStyle(KeyCase::SNAKE)]
readonly class Issue
{
    public function __construct(
        public int $number,
        public string $title,
        public GitHubUser $user,
        public InstantInterface $createdAt,
        public ?PullRequestLink $pullRequest = null,
    ) {
    }
}
