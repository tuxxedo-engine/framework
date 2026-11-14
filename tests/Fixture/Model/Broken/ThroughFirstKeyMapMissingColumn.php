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

namespace Fixture\Model\Broken;

use Fixture\Model\Composite\CompositeProject;
use Fixture\Model\Composite\CompositeTask;
use Tuxxedo\Model\Attribute\Column\Varchar;
use Tuxxedo\Model\Attribute\CompositeKey;
use Tuxxedo\Model\Attribute\Relation\HasManyThrough;
use Tuxxedo\Model\Attribute\Table;
use Tuxxedo\Model\Relation;

#[Table(name: 'through_first_key_map_missing_column')]
#[CompositeKey('scope', 'name')]
class ThroughFirstKeyMapMissingColumn
{
    #[Varchar(length: 64)]
    public string $scope = '';

    #[Varchar(length: 64)]
    public string $name = '';

    /**
     * @var Relation<CompositeTask>|null
     */
    #[HasManyThrough(
        related: CompositeTask::class,
        through: CompositeProject::class,
        firstKey: [
            'scope' => 'owner_scope',
            'label' => 'owner_name',
        ],
        secondKey: [
            'project_space',
            'project_slug',
        ],
    )]
    public ?Relation $tasks = null;
}
