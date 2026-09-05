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
use Tuxxedo\Model\Attribute\Relation\MorphTo;
use Tuxxedo\Model\Attribute\Table;

#[Table(name: 'morph_to_type_unknown')]
class MorphToTypeColumnUnknown
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    #[Integer(name: 'subject_id')]
    public int $subjectId = 0;

    #[MorphTo(typeColumn: 'missing_type', idColumn: 'subject_id')]
    public ?object $subject = null;
}
