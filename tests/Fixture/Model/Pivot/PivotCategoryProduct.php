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

namespace Fixture\Model\Pivot;

use Fixture\Model\Priority;
use Tuxxedo\Model\Attribute\Column\Enumeration;
use Tuxxedo\Model\Attribute\Column\Integer;
use Tuxxedo\Model\Attribute\Column\Varchar;
use Tuxxedo\Model\Attribute\CompositeKey;
use Tuxxedo\Model\Attribute\Table;

#[Table(name: 'pivot_category_product')]
#[CompositeKey('productId', 'categoryId')]
class PivotCategoryProduct
{
    #[Integer(name: 'product_id')]
    public int $productId = 0;

    #[Integer(name: 'category_id')]
    public int $categoryId = 0;

    #[Integer]
    public int $sort = 0;

    #[Enumeration(enum: Priority::class)]
    public Priority $priority = Priority::LOW;

    #[Varchar(length: 100, nullable: true)]
    public ?string $note = null;
}
