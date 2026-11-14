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

namespace Tuxxedo\Http\Client\Auth;

use Tuxxedo\Http\Client\HttpClientInterface;
use Tuxxedo\Http\Client\Request\ClientRequestInterface;
use Tuxxedo\Http\Client\Response\ClientResponseInterface;
use Tuxxedo\Http\Header;
use Tuxxedo\Http\Url\UrlInterface;
use Tuxxedo\Temporal\ClockInterface;
use Tuxxedo\Temporal\SystemClock;

class ClientCredentialsAuth implements RefreshableAuthInterface
{
    private readonly TokenEndpoint $endpoint;
    private readonly ClockInterface $clock;
    private ?AccessTokenInterface $token = null;

    /**
     * @param list<string> $scopes
     */
    public function __construct(
        UrlInterface|string $tokenUrl,
        string $clientId,
        #[\SensitiveParameter] string $clientSecret,
        private readonly array $scopes = [],
        bool $credentialsInBody = false,
        ?ClockInterface $clock = null,
    ) {
        $this->endpoint = new TokenEndpoint(
            url: $tokenUrl,
            clientId: $clientId,
            clientSecret: $clientSecret,
            credentialsInBody: $credentialsInBody,
        );

        $this->clock = $clock ?? new SystemClock();
    }

    public function apply(
        ClientRequestInterface $request,
        HttpClientInterface $httpClient,
    ): ClientRequestInterface {
        $now = $this->clock->now();

        if ($this->token === null || $this->token->isExpired($now)) {
            $fields = [
                'grant_type' => 'client_credentials',
            ];

            if (\sizeof($this->scopes) > 0) {
                $fields['scope'] = \join(' ', $this->scopes);
            }

            $this->token = $this->endpoint->request($httpClient, $fields, $now);
        }

        return $request->withHeader(
            header: new Header('Authorization', $this->token->tokenType . ' ' . $this->token->accessToken),
            replace: true,
        );
    }

    public function refresh(
        ClientResponseInterface $response,
        HttpClientInterface $httpClient,
    ): bool {
        if ($response->statusCode !== 401 || $this->token === null) {
            return false;
        }

        $this->token = null;

        return true;
    }
}
