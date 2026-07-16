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

use Tuxxedo\Model\Attribute\Column\Blob;
use Tuxxedo\Model\Attribute\Column\Date;
use Tuxxedo\Model\Attribute\Column\Integer;
use Tuxxedo\Model\Attribute\Column\Json;
use Tuxxedo\Model\Attribute\Column\Text;
use Tuxxedo\Model\Attribute\PrimaryKey;
use Tuxxedo\Model\Attribute\Relation\BelongsTo;
use Tuxxedo\Model\Attribute\Table;

#[Table(name: 'profiles')]
class Profile
{
    #[PrimaryKey]
    #[Integer]
    public ?int $id = null;

    #[Integer(name: 'user_id')]
    public int $userId = 0;

    #[Text]
    public string $bio = '';

    #[Blob]
    public ?string $avatar = null;

    /**
     * @var array<string, mixed>|null
     */
    #[Json]
    public ?array $settings = null;

    #[Date]
    public ?\DateTimeImmutable $birthDate = null;

    #[BelongsTo(related: User::class, foreignKey: 'user_id')]
    public ?User $user = null;
}
