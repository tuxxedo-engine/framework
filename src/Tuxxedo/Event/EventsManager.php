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

namespace Tuxxedo\Event;

use Tuxxedo\Container\ContainerInterface;
use Tuxxedo\Event\Attribute\Event;
use Tuxxedo\Event\Attribute\Listener;
use Tuxxedo\Reflection\ClassReflector;

class EventsManager implements EventsManagerInterface
{
    /**
     * @var array<array{0: \Closure(): object, 1: ListenerPriority}>
     */
    private array $subscribers = [];

    /**
     * @var array<DispatchableListenerInterface[]>
     */
    private array $listeners = [];

    public function __construct(
        private readonly ContainerInterface $container,
    ) {
    }

    public function registerSubscriber(
        string|object $subscriber,
        ListenerPriority $priority = ListenerPriority::NORMAL,
    ): void {
        if (\is_string($subscriber)) {
            $subscriber = fn (): object => $this->container->resolve($subscriber);
        } elseif (!$subscriber instanceof \Closure) {
            $subscriber = fn (): object => $subscriber;
        }

        $this->subscribers[] = [
            $subscriber,
            $priority,
        ];
    }

    private function discoverListeners(): void
    {
        foreach ($this->subscribers as [$subscriber, $priority]) {
            $subscriber = $subscriber();

            foreach (ClassReflector::createFromObject($subscriber)->methodsWithAttribute(Listener::class) as $method) {
                $eventParameter = \iterator_to_array($method->parametersWithAttribute(Event::class))[0] ?? null;
                $eventType = $eventParameter?->getDefaultType();
                $eventCallback = [
                    $subscriber,
                    $method->name,
                ];

                if (
                    $eventParameter === null ||
                    $eventType === null ||
                    !\is_callable($eventCallback)
                ) {
                    continue;
                }

                /** @var \Closure(): void */
                $eventCallback = $eventCallback(...);

                $this->listeners[$eventType] ??= [];
                $this->listeners[$eventType][] = new DispatchableListener(
                    callback: $eventCallback,
                    eventName: $eventParameter->name,
                    priority: $method->getAttribute(Listener::class)->priority ?? $priority,
                );
            }
        }

        foreach (\array_keys($this->listeners) as $eventType) {
            \uasort(
                $this->listeners[$eventType],
                static fn (DispatchableListenerInterface $a, DispatchableListenerInterface $b): int => $a->priority->value <=> $b->priority->value,
            );
        }

        $this->subscribers = [];
    }

    /**
     * @param class-string $eventClass
     * @return DispatchableListenerInterface[]
     */
    private function findListenersFor(
        string $eventClass,
    ): array {
        $this->discoverListeners();

        return $this->listeners[$eventClass] ?? [];
    }

    public function fire(
        object $event,
    ): void {
        foreach ($this->findListenersFor($event::class) as $listener) {
            if (
                $event instanceof StoppableEventInterface &&
                $event->propagationStopped
            ) {
                return;
            }

            $this->container->call(
                $listener->callback,
                [
                    $listener->eventName => $event,
                ],
            );
        }
    }

    public function fireLazy(
        string $eventClass,
        \Closure $event,
    ): void {
        if ($this->findListenersFor($eventClass) === []) {
            return;
        }

        $this->fire($event());
    }
}
