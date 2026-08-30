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

namespace Support\Mail;

class MailpitApiClient
{
    public function __construct(
        private readonly string $baseUrl,
    ) {
    }

    public function deleteAll(): void
    {
        $this->request(
            method: 'DELETE',
            path: '/messages',
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function list(): array
    {
        $body = $this->request(
            method: 'GET',
            path: '/messages',
        );

        /** @var array{messages?: list<array<string, mixed>>} $data */
        $data = \json_decode(
            json: $body,
            associative: true,
            flags: \JSON_THROW_ON_ERROR,
        );

        return $data['messages'] ?? [];
    }

    public function fetchRaw(
        string $id,
    ): string {
        return $this->request(
            method: 'GET',
            path: '/message/' . $id . '/raw',
        );
    }

    private function request(
        string $method,
        string $path,
    ): string {
        $context = \stream_context_create(
            options: [
                'http' => [
                    'method' => $method,
                    'ignore_errors' => true,
                    'timeout' => 5,
                    'header' => "Accept: application/json\r\n",
                ],
            ],
        );

        $body = @\file_get_contents(
            filename: $this->baseUrl . $path,
            context: $context,
        );

        if ($body === false) {
            throw new \RuntimeException(
                message: \sprintf(
                    'Mailpit API request failed: %s %s',
                    $method,
                    $this->baseUrl . $path,
                ),
            );
        }

        return $body;
    }
}
