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

use Tuxxedo\Model\Attribute\Column\Integer;
use Tuxxedo\Model\Attribute\Column\Varchar;
use Tuxxedo\Model\Attribute\Relation\MorphTo;
use Tuxxedo\Model\Attribute\Table;

#[Table(name: 'morph_to_id_unknown')]
class MorphToIdColumnUnknown
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    #[Varchar(name: 'subject_type', length: 64)]
    public string $subjectType = '';

    #[MorphTo(typeColumn: 'subject_type', idColumn: 'missing_id')]
    public ?object $subject = null;
}
