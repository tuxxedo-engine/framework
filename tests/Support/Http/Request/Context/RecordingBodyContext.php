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

namespace Support\Http\Request\Context;

use Tuxxedo\Http\HttpException;

class RecordingBodyContext extends StubBodyContext
{
    /**
     * @var array{className: string|object, flags: int}|null
     */
    public ?array $jsonMapToCall = null;

    /**
     * @var array{className: string|object, flags: int}|null
     */
    public ?array $jsonMapToArrayOfCall = null;

    public bool $throwOnMap = false;

    /**
     * @param object[] $jsonMapToArrayOfReturn
     */
    public function __construct(
        public readonly ?object $jsonMapToReturn = null,
        public readonly array $jsonMapToArrayOfReturn = [],
    ) {
    }

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName>|(\Closure(): TClassName)|TClassName $className
     * @return TClassName
     */
    public function jsonMapTo(
        string|object $className,
        int $flags = 0,
    ): object {
        $this->jsonMapToCall = [
            'className' => $className,
            'flags' => $flags,
        ];

        if ($this->throwOnMap) {
            throw HttpException::fromInternalServerError();
        }

        if ($this->jsonMapToReturn === null) {
            throw HttpException::fromInternalServerError();
        }

        /** @var TClassName */
        return $this->jsonMapToReturn;
    }

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName>|(\Closure(): TClassName)|TClassName $className
     * @return TClassName[]
     */
    public function jsonMapToArrayOf(
        string|object $className,
        int $flags = 0,
    ): array {
        $this->jsonMapToArrayOfCall = [
            'className' => $className,
            'flags' => $flags,
        ];

        if ($this->throwOnMap) {
            throw HttpException::fromInternalServerError();
        }

        /** @var TClassName[] */
        return $this->jsonMapToArrayOfReturn;
    }
}
