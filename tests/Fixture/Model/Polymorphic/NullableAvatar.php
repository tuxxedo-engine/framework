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

namespace Fixture\Model\Polymorphic;

use Tuxxedo\Model\Attribute\Column\Integer;
use Tuxxedo\Model\Attribute\Column\Varchar;
use Tuxxedo\Model\Attribute\Table;

#[Table(name: 'nullable_avatars')]
class NullableAvatar
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    #[Varchar(name: 'subject_type', length: 64)]
    public ?string $subjectType = null;

    #[Integer(name: 'subject_id')]
    public ?int $subjectId = null;

    #[Varchar(length: 255)]
    public string $url = '';
}
