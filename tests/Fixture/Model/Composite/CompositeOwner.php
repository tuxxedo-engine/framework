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

namespace Fixture\Model\Composite;

use Tuxxedo\Model\Attribute\Column\Varchar;
use Tuxxedo\Model\Attribute\CompositeKey;
use Tuxxedo\Model\Attribute\Relation\BelongsToMany;
use Tuxxedo\Model\Attribute\Relation\HasMany;
use Tuxxedo\Model\Attribute\Relation\HasOne;
use Tuxxedo\Model\Attribute\Relation\MorphMany;
use Tuxxedo\Model\Attribute\Relation\MorphToMany;
use Tuxxedo\Model\Attribute\Table;
use Tuxxedo\Model\CascadeAction;
use Tuxxedo\Model\Relation;

#[Table(name: 'composite_owners')]
#[CompositeKey('scope', 'name')]
class CompositeOwner
{
    #[Varchar(length: 64)]
    public string $scope = '';

    #[Varchar(length: 64)]
    public string $name = '';

    #[Varchar(length: 255)]
    public string $label = '';

    #[HasOne(
        related: CompositeOwnerHasOneChild::class,
        foreignKey: [
            'owner_scope',
            'owner_name',
        ],
    )]
    public ?CompositeOwnerHasOneChild $singleChild = null;

    /**
     * @var Relation<CompositeOwnerHasManyChild>|null
     */
    #[HasMany(
        related: CompositeOwnerHasManyChild::class,
        foreignKey: [
            'owner_scope',
            'owner_name',
        ],
    )]
    public ?Relation $manyChildren = null;

    /**
     * @var Relation<CompositeTag>|null
     */
    #[BelongsToMany(
        related: CompositeTag::class,
        table: 'composite_owner_tags',
        localKey: [
            'owner_scope',
            'owner_name',
        ],
        foreignKey: [
            'tag_realm',
            'tag_code',
        ],
    )]
    public ?Relation $tags = null;

    /**
     * @var Relation<CompositeMorphNote>|null
     */
    #[MorphMany(
        related: CompositeMorphNote::class,
        typeColumn: 'ownerType',
        idColumn: [
            'ownerScope',
            'ownerName',
        ],
        onSave: CascadeAction::CASCADE,
    )]
    public ?Relation $notes = null;

    /**
     * @var Relation<CompositeTag>|null
     */
    #[MorphToMany(
        related: CompositeTag::class,
        table: 'composite_morph_tags',
        typeColumn: 'owner_type',
        idColumn: [
            'owner_scope',
            'owner_name',
        ],
        foreignKey: [
            'tag_realm',
            'tag_code',
        ],
    )]
    public ?Relation $morphTags = null;
}
