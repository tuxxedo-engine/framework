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

namespace Fixture\Model;

use Tuxxedo\Model\Attribute\Column\Integer;
use Tuxxedo\Model\Attribute\Column\Varchar;
use Tuxxedo\Model\Attribute\PrimaryKey;
use Tuxxedo\Model\Attribute\Relation\BelongsToMany;
use Tuxxedo\Model\Attribute\Relation\HasMany;
use Tuxxedo\Model\Attribute\Relation\HasOne;
use Tuxxedo\Model\Attribute\Table;
use Tuxxedo\Model\CascadeAction;
use Tuxxedo\Model\Relation;

#[Table(name: 'cascade_groups')]
class CascadeGroup
{
    #[PrimaryKey]
    #[Integer]
    public ?int $id = null;

    #[Varchar(length: 255)]
    public string $name = '';

    #[HasOne(
        related: CascadeHasOneChild::class,
        foreignKey: 'group_id',
        onSave: CascadeAction::CASCADE,
        onDelete: CascadeAction::CASCADE,
    )]
    public ?CascadeHasOneChild $hasOneChild = null;

    /**
     * @var Relation<CascadeChild>|null
     */
    #[HasMany(
        related: CascadeChild::class,
        foreignKey: 'auto_group_id',
        onSave: CascadeAction::CASCADE,
        onDelete: CascadeAction::CASCADE,
    )]
    public ?Relation $autoChildren = null;

    /**
     * @var Relation<CascadeChild>|null
     */
    #[HasMany(
        related: CascadeChild::class,
        foreignKey: 'restrict_group_id',
        onDelete: CascadeAction::RESTRICT,
    )]
    public ?Relation $restrictChildren = null;

    /**
     * @var Relation<CascadeChild>|null
     */
    #[HasMany(
        related: CascadeChild::class,
        foreignKey: 'nullable_group_id',
        onDelete: CascadeAction::SET_NULL,
    )]
    public ?Relation $nullableChildren = null;

    /**
     * @var Relation<CascadeChild>|null
     */
    #[HasMany(
        related: CascadeChild::class,
        foreignKey: 'noaction_group_id',
    )]
    public ?Relation $noActionChildren = null;

    /**
     * @var Relation<CascadeTag>|null
     */
    #[BelongsToMany(
        related: CascadeTag::class,
        table: 'cascade_group_tag',
        localKey: 'group_id',
        foreignKey: 'tag_id',
        onSave: CascadeAction::CASCADE,
        onDelete: CascadeAction::CASCADE,
    )]
    public ?Relation $tags = null;
}
