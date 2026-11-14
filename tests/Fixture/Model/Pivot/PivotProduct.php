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

use Tuxxedo\Model\Attribute\Column\Integer;
use Tuxxedo\Model\Attribute\Column\Varchar;
use Tuxxedo\Model\Attribute\Relation\BelongsToMany;
use Tuxxedo\Model\Attribute\Relation\MorphToMany;
use Tuxxedo\Model\Attribute\Table;
use Tuxxedo\Model\Relation;

#[Table(name: 'pivot_products')]
class PivotProduct
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    #[Varchar(length: 100)]
    public string $name = '';

    /**
     * @var Relation<PivotCategory>|null
     */
    #[BelongsToMany(
        related: PivotCategory::class,
        table: 'pivot_category_product',
        localKey: 'product_id',
        foreignKey: 'category_id',
        pivot: PivotCategoryProduct::class,
    )]
    public ?Relation $categories = null;

    /**
     * @var Relation<PivotCategory>|null
     */
    #[MorphToMany(
        related: PivotCategory::class,
        table: 'pivot_labelables',
        typeColumn: 'labelable_type',
        idColumn: 'labelable_id',
        foreignKey: 'category_id',
        pivot: PivotLabelable::class,
    )]
    public ?Relation $labels = null;
}
