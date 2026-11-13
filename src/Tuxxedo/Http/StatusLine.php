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

namespace Tuxxedo\Http;

use Tuxxedo\Http\Response\ResponseCode;

readonly class StatusLine implements StatusLineInterface
{
    public ResponseCode $responseCode;

    /**
     * @throws HttpException
     */
    final public function __construct(
        public HttpVersion $httpVersion,
        public int $statusCode,
        public string $reasonPhrase = '',
    ) {
        $this->responseCode = ResponseCode::resolve($statusCode);
    }

    /**
     * @throws HttpException
     */
    public static function parse(
        string $line,
    ): static {
        if (\preg_match('/^HTTP\/(\d)(?:\.(\d))? +(\d{3})(?: +(.*))?$/', \rtrim($line, "\r\n"), $matches) !== 1) {
            throw HttpException::fromInternalServerError();
        }

        $httpVersion = HttpVersion::tryFrom(
            \sprintf(
                'HTTP/%s.%s',
                $matches[1],
                $matches[2] !== ''
                    ? $matches[2]
                    : '0',
            ),
        );

        if ($httpVersion === null) {
            throw HttpException::fromInternalServerError();
        }

        return new static(
            httpVersion: $httpVersion,
            statusCode: (int) $matches[3],
            reasonPhrase: $matches[4] ?? '',
        );
    }
}
