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

namespace Fixture\Model\Aggregate;

use Tuxxedo\Model\Attribute\Column\Integer;
use Tuxxedo\Model\Attribute\Column\Varchar;
use Tuxxedo\Model\Attribute\Relation\BelongsTo;
use Tuxxedo\Model\Attribute\Table;

#[Table(name: 'aggregate_named_roots')]
class AggregateNamedRoot
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    #[Varchar(length: 100)]
    public string $name = '';

    #[Integer(name: 'audit_id')]
    public ?int $auditId = null;

    #[BelongsTo(
        related: OnConnectionAuditModel::class,
        foreignKey: 'audit_id',
    )]
    public ?OnConnectionAuditModel $audit = null;
}
