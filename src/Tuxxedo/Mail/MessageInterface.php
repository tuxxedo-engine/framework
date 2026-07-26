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

namespace Tuxxedo\Mail;

interface MessageInterface
{
    public AddressInterface $from {
        get;
    }

    /**
     * @var list<AddressInterface>
     */
    public array $to {
        get;
    }

    public string $subject {
        get;
    }

    public ?string $textBody {
        get;
    }

    public ?string $htmlBody {
        get;
    }

    /**
     * @var list<AddressInterface>
     */
    public array $cc {
        get;
    }

    /**
     * @var list<AddressInterface>
     */
    public array $bcc {
        get;
    }

    /**
     * @var list<AddressInterface>
     */
    public array $replyTo {
        get;
    }

    public ?AddressInterface $sender {
        get;
    }

    public ?AddressInterface $returnPath {
        get;
    }

    public string $messageId {
        get;
    }

    public \DateTimeImmutable $date {
        get;
    }
}
