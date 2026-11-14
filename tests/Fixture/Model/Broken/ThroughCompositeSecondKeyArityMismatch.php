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
use Tuxxedo\Model\Attribute\Column\Integer;
use Tuxxedo\Model\Attribute\Relation\HasManyThrough;
use Tuxxedo\Model\Attribute\Table;
use Tuxxedo\Model\Relation;

#[Table(name: 'through_composite_second_key_arity_mismatch')]
class ThroughCompositeSecondKeyArityMismatch
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    /**
     * @var Relation<CompositeTask>|null
     */
    #[HasManyThrough(
        related: CompositeTask::class,
        through: CompositeProject::class,
        firstKey: 'owner_scope',
        secondKey: 'project_space',
    )]
    public ?Relation $tasks = null;
}
