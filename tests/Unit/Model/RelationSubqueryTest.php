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

namespace Unit\Model;

use PHPUnit\Framework\TestCase;
use Tuxxedo\Model\ModelException;
use Tuxxedo\Model\RelationSubquery;

class RelationSubqueryTest extends TestCase
{
    public function testWhereHasWithoutModelContextThrows(): void
    {
        $this->expectException(ModelException::class);

        (new RelationSubquery(
            table: 'comments',
        ))->whereHas('author');
    }

    public function testWhereDoesntHaveWithoutModelContextThrows(): void
    {
        $this->expectException(ModelException::class);

        (new RelationSubquery(
            table: 'comments',
        ))->whereDoesntHave('author');
    }
}
