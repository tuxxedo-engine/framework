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

class SendResult implements SendResultInterface
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
     * @var list<RecipientOutcomeInterface>
     */
    public array $accepted {
        get {
            return $this->filterByStatus(RecipientStatus::ACCEPTED);
        }
    }

    /**
     * @var list<RecipientOutcomeInterface>
     */
    public array $failed {
        get {
            return \array_values(
                \array_filter(
                    $this->outcomes,
                    static fn (RecipientOutcomeInterface $outcome): bool => $outcome->status !== RecipientStatus::ACCEPTED,
                ),
            );
        }
    }

    /**
     * @var list<RecipientOutcomeInterface>
     */
    public array $transientlyFailed {
        get {
            return $this->filterByStatus(RecipientStatus::TRANSIENT_FAILURE);
        }
    }

    /**
     * @var list<RecipientOutcomeInterface>
     */
    public array $permanentlyFailed {
        get {
            return $this->filterByStatus(RecipientStatus::PERMANENT_FAILURE);
        }
    }

    /**
     * @param list<RecipientOutcomeInterface> $outcomes
     */
    public function __construct(
        public readonly MessageInterface $message,
        public readonly array $outcomes,
    ) {
    }

    /**
     * @return list<RecipientOutcomeInterface>
     */
    private function filterByStatus(
        RecipientStatus $status,
    ): array {
        return \array_values(
            \array_filter(
                $this->outcomes,
                static fn (RecipientOutcomeInterface $outcome): bool => $outcome->status === $status,
            ),
        );
    }
}
