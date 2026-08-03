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

namespace Tuxxedo\Mail\Result;

use Tuxxedo\Mail\MessageInterface;

interface SendResultInterface
{
    public MessageInterface $message {
        get;
    }

    /**
     * @var list<RecipientOutcomeInterface>
     */
    public array $outcomes {
        get;
    }

    public int $acceptedCount {
        get;
    }

    public int $failedCount {
        get;
    }

    public bool $isFullSuccess {
        get;
    }

    public bool $isPartialSuccess {
        get;
    }

    public bool $isFailure {
        get;
    }

    /**
     * @var list<RecipientOutcomeInterface>
     */
    public array $accepted {
        get;
    }

    /**
     * @var list<RecipientOutcomeInterface>
     */
    public array $failed {
        get;
    }

    /**
     * @var list<RecipientOutcomeInterface>
     */
    public array $transientlyFailed {
        get;
    }

    /**
     * @var list<RecipientOutcomeInterface>
     */
    public array $permanentlyFailed {
        get;
    }
}
