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

namespace Unit\Event;

use Fixture\Event\InjectedDependency;
use Fixture\Event\OrderPlaced;
use Fixture\Event\StoppableSignal;
use Fixture\Event\Subscriber\FallbackPrioritySubscriber;
use Fixture\Event\Subscriber\InjectedSubscriber;
use Fixture\Event\Subscriber\MixedSubscriber;
use Fixture\Event\Subscriber\PriorityOrderedSubscriber;
use Fixture\Event\Subscriber\RecordingSubscriber;
use Fixture\Event\Subscriber\StoppingSubscriber;
use Fixture\Event\UnrelatedEvent;
use PHPUnit\Framework\TestCase;
use Tuxxedo\Container\Container;
use Tuxxedo\Event\EventsManager;
use Tuxxedo\Event\ListenerPriority;

class EventsManagerTest extends TestCase
{
    private function makeManager(): EventsManager
    {
        return new EventsManager(
            container: new Container(),
        );
    }

    public function testFireWithNoSubscribersIsNoop(): void
    {
        $manager = $this->makeManager();

        self::expectNotToPerformAssertions();

        $manager->fire(
            event: new OrderPlaced(
                orderId: 'order-1',
            ),
        );
    }

    public function testRegisteredSubscriberReceivesMatchingEvent(): void
    {
        $subscriber = new RecordingSubscriber();
        $manager = $this->makeManager();

        $manager->registerSubscriber(
            subscriber: $subscriber,
        );

        $manager->fire(
            event: new OrderPlaced(
                orderId: 'order-1',
            ),
        );

        self::assertSame(
            [
                'order:order-1',
            ],
            $subscriber->calls,
        );
    }

    public function testListenersForOtherEventClassesAreNotInvoked(): void
    {
        $subscriber = new RecordingSubscriber();
        $manager = $this->makeManager();

        $manager->registerSubscriber(
            subscriber: $subscriber,
        );

        $manager->fire(
            event: new UnrelatedEvent(),
        );

        self::assertSame([], $subscriber->calls);
    }

    public function testListenersAreInvokedInAscendingPriorityValueOrder(): void
    {
        $subscriber = new PriorityOrderedSubscriber();
        $manager = $this->makeManager();

        $manager->registerSubscriber(
            subscriber: $subscriber,
        );

        $manager->fire(
            event: new OrderPlaced(
                orderId: 'order-1',
            ),
        );

        self::assertSame(
            [
                'high',
                'normal',
                'low',
            ],
            $subscriber->calls,
        );
    }

    public function testListenerAttributePriorityOverridesRegistrationPriority(): void
    {
        $subscriber = new FallbackPrioritySubscriber();
        $manager = $this->makeManager();

        $manager->registerSubscriber(
            subscriber: $subscriber,
            priority: ListenerPriority::LOW,
        );

        $manager->fire(
            event: new OrderPlaced(
                orderId: 'order-1',
            ),
        );

        self::assertSame(
            [
                'attribute-high',
                'fallback',
            ],
            $subscriber->calls,
        );
    }

    public function testRegistrationPriorityIsUsedWhenListenerAttributeHasNoPriority(): void
    {
        $high = new RecordingSubscriber();
        $low = new RecordingSubscriber();
        $manager = $this->makeManager();

        $manager->registerSubscriber(
            subscriber: $low,
            priority: ListenerPriority::LOW,
        );

        $manager->registerSubscriber(
            subscriber: $high,
            priority: ListenerPriority::HIGH,
        );

        $manager->fire(
            event: new OrderPlaced(
                orderId: 'order-1',
            ),
        );

        self::assertSame(
            [
                'order:order-1',
            ],
            $high->calls,
        );

        self::assertSame(
            [
                'order:order-1',
            ],
            $low->calls,
        );
    }

    public function testStoppingPropagationPreventsSubsequentListeners(): void
    {
        $subscriber = new StoppingSubscriber();
        $manager = $this->makeManager();

        $manager->registerSubscriber(
            subscriber: $subscriber,
        );

        $manager->fire(
            event: new StoppableSignal(),
        );

        self::assertSame(
            [
                'stop',
            ],
            $subscriber->calls,
        );
    }

    public function testOnlyMethodsTaggedAsListenerWithEventParameterAreInvoked(): void
    {
        $subscriber = new MixedSubscriber();
        $manager = $this->makeManager();

        $manager->registerSubscriber(
            subscriber: $subscriber,
        );

        $manager->fire(
            event: new OrderPlaced(
                orderId: 'order-1',
            ),
        );

        self::assertSame(
            [
                'tagged',
            ],
            $subscriber->calls,
        );
    }

    public function testSubscriberRegisteredByClassStringIsResolvedFromContainer(): void
    {
        $subscriber = new RecordingSubscriber();
        $container = (new Container())->singleton(
            class: $subscriber,
        );

        $manager = new EventsManager(
            container: $container,
        );

        $manager->registerSubscriber(
            subscriber: RecordingSubscriber::class,
        );

        $manager->fire(
            event: new OrderPlaced(
                orderId: 'order-1',
            ),
        );

        self::assertSame(
            [
                'order:order-1',
            ],
            $subscriber->calls,
        );
    }

    public function testSubscriberRegisteredAsClosureIsInvoked(): void
    {
        $subscriber = new RecordingSubscriber();
        $manager = $this->makeManager();

        $manager->registerSubscriber(
            subscriber: static fn (): RecordingSubscriber => $subscriber,
        );

        $manager->fire(
            event: new OrderPlaced(
                orderId: 'order-1',
            ),
        );

        self::assertSame(
            [
                'order:order-1',
            ],
            $subscriber->calls,
        );
    }

    public function testFireLazyDoesNotInvokeFactoryWhenNoListeners(): void
    {
        $manager = $this->makeManager();
        $invoked = false;

        $manager->fireLazy(
            eventClass: OrderPlaced::class,
            event: static function () use (&$invoked): OrderPlaced {
                $invoked = true;

                return new OrderPlaced(
                    orderId: 'lazy',
                );
            },
        );

        self::assertFalse($invoked);
    }

    public function testFireLazyInvokesFactoryAndDispatchesWhenListenersExist(): void
    {
        $subscriber = new RecordingSubscriber();
        $manager = $this->makeManager();

        $manager->registerSubscriber(
            subscriber: $subscriber,
        );

        $manager->fireLazy(
            eventClass: OrderPlaced::class,
            event: static fn (): OrderPlaced => new OrderPlaced(
                orderId: 'lazy',
            ),
        );

        self::assertSame(
            [
                'order:lazy',
            ],
            $subscriber->calls,
        );
    }

    public function testContainerInjectsAdditionalListenerDependencies(): void
    {
        $dependency = new InjectedDependency(
            value: 'injected',
        );

        $container = (new Container())->singleton(
            class: $dependency,
        );

        $manager = new EventsManager(
            container: $container,
        );

        $subscriber = new InjectedSubscriber();

        $manager->registerSubscriber(
            subscriber: $subscriber,
        );

        $manager->fire(
            event: new OrderPlaced(
                orderId: 'order-1',
            ),
        );

        self::assertSame(
            [
                'order-1:injected',
            ],
            $subscriber->calls,
        );
    }

    public function testSubscribersRegisteredAfterFireAreDiscoveredOnNextFire(): void
    {
        $first = new RecordingSubscriber();
        $second = new RecordingSubscriber();
        $manager = $this->makeManager();

        $manager->registerSubscriber(
            subscriber: $first,
        );

        $manager->fire(
            event: new OrderPlaced(
                orderId: 'one',
            ),
        );

        $manager->registerSubscriber(
            subscriber: $second,
        );

        $manager->fire(
            event: new OrderPlaced(
                orderId: 'two',
            ),
        );

        self::assertSame(
            [
                'order:one',
                'order:two',
            ],
            $first->calls,
        );

        self::assertSame(
            [
                'order:two',
            ],
            $second->calls,
        );
    }
}
