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

namespace Tuxxedo\Http\Client;

use Tuxxedo\Http\Client\Request\ClientRequestInterface;
use Tuxxedo\Http\Client\Response\ClientResponseInterface;
use Tuxxedo\Http\Client\Rules\Rule;
use Tuxxedo\Http\Client\Rules\RuleAction;
use Tuxxedo\Net\IpRangeList;
use Tuxxedo\Net\IpRangeListInterface;
use Tuxxedo\Net\NetException;

class HttpRules implements HttpRulesInterface
{
    private const array PRIVATE_NETWORKS = [
        '0.0.0.0/8',
        '10.0.0.0/8',
        '100.64.0.0/10',
        '127.0.0.0/8',
        '169.254.0.0/16',
        '172.16.0.0/12',
        '192.168.0.0/16',
        '::/128',
        '::1/128',
        'fc00::/7',
        'fe80::/10',
    ];

    /**
     * @var list<Rule>
     */
    private array $rules = [];

    private ?IpRangeListInterface $privateNetworks = null;
    private bool $denyUnmatched = false;

    /**
     * @throws NetException
     */
    public function allow(
        string ...$patterns,
    ): static {
        return $this->with(RuleAction::ALLOW, $patterns);
    }

    /**
     * @throws NetException
     */
    public function deny(
        string ...$patterns,
    ): static {
        return $this->with(RuleAction::DENY, $patterns);
    }

    /**
     * @param ClientResponseInterface|list<ClientResponseInterface>|(\Closure(ClientRequestInterface): ClientResponseInterface) $response
     *
     * @throws NetException
     */
    public function respond(
        string $pattern,
        ClientResponseInterface|array|\Closure $response,
    ): static {
        if ($response instanceof \Closure) {
            $handler = $response;
        } elseif (\is_array($response)) {
            $handler = static function (ClientRequestInterface $request) use (&$response, $pattern): ClientResponseInterface|HttpClientException {
                return \array_shift($response) ?? HttpClientException::fromExhaustedSequence($request, $pattern);
            };
        } else {
            $handler = static fn (): ClientResponseInterface => $response;
        }

        return $this->with(
            action: RuleAction::RESPOND,
            patterns: [
                $pattern,
            ],
            handler: $handler,
        );
    }

    /**
     * @param \Closure(ClientRequestInterface): HttpClientException $exception
     *
     * @throws NetException
     */
    public function fail(
        string $pattern,
        \Closure $exception,
    ): static {
        return $this->with(
            action: RuleAction::FAIL,
            patterns: [
                $pattern,
            ],
            handler: $exception,
        );
    }

    /**
     * @throws NetException
     */
    public function denyPrivateNetworks(): static
    {
        return clone (
            $this,
            [
                'privateNetworks' => new IpRangeList(self::PRIVATE_NETWORKS),
            ],
        );
    }

    public function denyUnmatched(): static
    {
        return clone (
            $this,
            [
                'denyUnmatched' => true,
            ],
        );
    }

    public function evaluate(
        ClientRequestInterface $request,
    ): ClientResponseInterface|HttpClientException|null {
        $host = \trim($request->url->host, '[]');

        if (\inet_pton($host) !== false && !$this->allowsAddress($host)) {
            throw HttpClientException::fromPrivateNetwork($request, $host);
        }

        foreach ($this->rules as $rule) {
            if (!$rule->matches($request)) {
                continue;
            }

            if ($rule->action === RuleAction::DENY) {
                throw HttpClientException::fromDeniedByRule($request, $rule->pattern);
            }

            if ($rule->handler === null) {
                return null;
            }

            return ($rule->handler)($request);
        }

        if ($this->denyUnmatched) {
            throw HttpClientException::fromUnmatchedRequest($request);
        }

        return null;
    }

    public function allowsAddress(
        string $address,
    ): bool {
        return $this->privateNetworks === null || !$this->privateNetworks->contains($address);
    }

    /**
     * @param string[] $patterns
     * @param (\Closure(ClientRequestInterface): (ClientResponseInterface|HttpClientException))|null $handler
     *
     * @throws NetException
     */
    private function with(
        RuleAction $action,
        array $patterns,
        ?\Closure $handler = null,
    ): static {
        $rules = $this->rules;

        foreach ($patterns as $pattern) {
            $rules[] = new Rule($pattern, $action, $handler);
        }

        return clone (
            $this,
            [
                'rules' => $rules,
            ],
        );
    }
}
