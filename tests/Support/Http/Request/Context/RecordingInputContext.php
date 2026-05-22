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

class RecordingInputContext extends StubInputContext
{
    /**
     * @var array{name: string, className: string|object}|null
     */
    public ?array $mapToCall = null;

    /**
     * @var array{name: string, className: string|object}|null
     */
    public ?array $mapToArrayOfCall = null;

    /**
     * @var array{name: string, className: string|object, flags: int}|null
     */
    public ?array $jsonMapToCall = null;

    /**
     * @var array{name: string, className: string|object, flags: int}|null
     */
    public ?array $jsonMapToArrayOfCall = null;

    public bool $throwOnMap = false;

    /**
     * @param object[] $mapToArrayOfReturn
     * @param object[] $jsonMapToArrayOfReturn
     */
    public function __construct(
        public readonly ?object $mapToReturn = null,
        public readonly array $mapToArrayOfReturn = [],
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
    public function mapTo(
        string $name,
        string|object $className,
    ): object {
        $this->mapToCall = [
            'name' => $name,
            'className' => $className,
        ];

        if ($this->throwOnMap) {
            throw HttpException::fromInternalServerError();
        }

        if ($this->mapToReturn === null) {
            throw HttpException::fromInternalServerError();
        }

        /** @var TClassName */
        return $this->mapToReturn;
    }

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName>|(\Closure(): TClassName)|TClassName $className
     * @return TClassName[]
     */
    public function mapToArrayOf(
        string $name,
        string|object $className,
    ): array {
        $this->mapToArrayOfCall = [
            'name' => $name,
            'className' => $className,
        ];

        if ($this->throwOnMap) {
            throw HttpException::fromInternalServerError();
        }

        /** @var TClassName[] */
        return $this->mapToArrayOfReturn;
    }

    /**
     * @template TClassName of object
     *
     * @param class-string<TClassName>|(\Closure(): TClassName)|TClassName $className
     * @return TClassName
     */
    public function jsonMapTo(
        string $name,
        string|object $className,
        int $flags = 0,
    ): object {
        $this->jsonMapToCall = [
            'name' => $name,
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
        string $name,
        string|object $className,
        int $flags = 0,
    ): array {
        $this->jsonMapToArrayOfCall = [
            'name' => $name,
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
