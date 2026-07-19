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

use Tuxxedo\Model\Attribute\Column\BigInteger;
use Tuxxedo\Model\Attribute\Column\Blob;
use Tuxxedo\Model\Attribute\Column\Boolean;
use Tuxxedo\Model\Attribute\Column\Char;
use Tuxxedo\Model\Attribute\Column\Date;
use Tuxxedo\Model\Attribute\Column\DateTime;
use Tuxxedo\Model\Attribute\Column\Decimal;
use Tuxxedo\Model\Attribute\Column\Double;
use Tuxxedo\Model\Attribute\Column\Enumeration;
use Tuxxedo\Model\Attribute\Column\Integer;
use Tuxxedo\Model\Attribute\Column\Json;
use Tuxxedo\Model\Attribute\Column\SmallInteger;
use Tuxxedo\Model\Attribute\Column\Text;
use Tuxxedo\Model\Attribute\Column\Time;
use Tuxxedo\Model\Attribute\Column\Timestamp;
use Tuxxedo\Model\Attribute\Column\TinyInteger;
use Tuxxedo\Model\Attribute\Column\Varchar;
use Tuxxedo\Model\Attribute\Table;

#[Table(name: 'all_column_types')]
class AllColumnTypes
{
    #[Integer(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    #[TinyInteger(name: 'tiny_value')]
    public int $tinyValue = 0;

    #[SmallInteger(name: 'small_value')]
    public int $smallValue = 0;

    #[Integer(name: 'int_value')]
    public int $intValue = 0;

    #[BigInteger(name: 'big_value')]
    public int $bigValue = 0;

    #[Boolean]
    public bool $flag = false;

    #[Double]
    public float $ratio = 0.0;

    #[Decimal(precision: 10, scale: 2)]
    public string $price = '0.00';

    #[Char(length: 3)]
    public string $code = '';

    #[Varchar(length: 64)]
    public string $label = '';

    #[Text]
    public string $body = '';

    #[Blob]
    public string $payload = '';

    #[Json]
    public string $meta = '{}';

    #[Enumeration(enum: PostStatus::class)]
    public PostStatus $status = PostStatus::DRAFT;

    #[Date(nullable: true)]
    public ?\DateTimeImmutable $day = null;

    #[DateTime(nullable: true)]
    public ?\DateTimeImmutable $at = null;

    #[Time(nullable: true)]
    public ?\DateTimeImmutable $clock = null;

    #[Timestamp(nullable: true)]
    public ?\DateTimeImmutable $stamped = null;
}
