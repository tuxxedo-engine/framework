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

namespace Support\Http\Client;

use Support\Serializer\SerializerManagerFactory;
use Tuxxedo\Http\Client\HttpClient;
use Tuxxedo\Http\Client\Request\ClientRequestInterface;

class AuthClient
{
    public static function create(
        ScriptedTransport $transport,
    ): HttpClient {
        return new HttpClient(
            serializerManager: SerializerManagerFactory::create(),
            transport: $transport,
        );
    }

    public static function header(
        ClientRequestInterface $request,
        string $name,
    ): ?string {
        foreach ($request->headers as $header) {
            if ($header->is($name)) {
                return $header->value;
            }
        }

        return null;
    }

    public static function body(
        ClientRequestInterface $request,
    ): string {
        $body = $request->body?->encode(SerializerManagerFactory::create());

        return \is_string($body)
            ? $body
            : '';
    }
}
