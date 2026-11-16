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

namespace Tuxxedo\Database\Query\FullText;

use Tuxxedo\Database\SqlException;

readonly class FullTextRelevance implements FullTextRelevanceInterface
{
    /**
     * @var list<string>
     */
    public array $terms;

    /**
     * @throws SqlException
     */
    public function __construct(
        public string $column,
        public string $search,
    ) {
        $this->terms = FullText::terms($search);
    }
}
