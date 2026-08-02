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

class SendResult
{
    public int $acceptedCount {
        get {
            return \sizeof($this->accepted);
        }
    }

    public int $failedCount {
        get {
            return \sizeof($this->failed);
        }
    }

    public bool $isFullSuccess {
        get {
            return $this->failedCount === 0;
        }
    }

    public bool $isPartialSuccess {
        get {
            return $this->acceptedCount > 0 && $this->failedCount > 0;
        }
    }

    public bool $isFailure {
        get {
            return $this->acceptedCount === 0;
        }
    }

    /**
     * @var list<RecipientOutcome>
     */
    public array $accepted {
        get {
            return $this->filterByStatus(RecipientStatus::ACCEPTED);
        }
    }

    /**
     * @var list<RecipientOutcome>
     */
    public array $failed {
        get {
            return \array_values(
                \array_filter(
                    $this->outcomes,
                    static fn (RecipientOutcome $outcome): bool => $outcome->status !== RecipientStatus::ACCEPTED,
                ),
            );
        }
    }

    /**
     * @var list<RecipientOutcome>
     */
    public array $transientlyFailed {
        get {
            return $this->filterByStatus(RecipientStatus::TRANSIENT_FAILURE);
        }
    }

    /**
     * @var list<RecipientOutcome>
     */
    public array $permanentlyFailed {
        get {
            return $this->filterByStatus(RecipientStatus::PERMANENT_FAILURE);
        }
    }

    /**
     * @param list<RecipientOutcome> $outcomes
     */
    public function __construct(
        public readonly MessageInterface $message,
        public readonly array $outcomes,
    ) {
    }

    /**
     * @return list<RecipientOutcome>
     */
    private function filterByStatus(
        RecipientStatus $status,
    ): array {
        return \array_values(
            \array_filter(
                $this->outcomes,
                static fn (RecipientOutcome $outcome): bool => $outcome->status === $status,
            ),
        );
    }
}
