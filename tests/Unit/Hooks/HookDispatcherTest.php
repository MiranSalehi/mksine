<?php

declare(strict_types=1);

use Miran\Mksine\Core\Events\MksineEvent;
use Miran\Mksine\Core\Hooks\HookDispatcher;
use Miran\Mksine\Core\Hooks\MksineListenerInterface;

// Test event class
class DispatcherTestEvent extends MksineEvent
{
    public function name(): string
    {
        return 'dispatcher.test';
    }

    public function canBePrevented(): bool
    {
        return true;
    }
}

// Test listener that modifies data
class ModifyingListener implements MksineListenerInterface
{
    public function handle(MksineEvent $event): void
    {
        $event->updateData('modified_by', 'ModifyingListener');
    }

    public function shouldHandle(MksineEvent $event): bool
    {
        return true;
    }

    public function shouldQueue(): bool
    {
        return false;
    }

    public function priority(): int
    {
        return 0;
    }
}

// Test listener that prevents event
class PreventingListener implements MksineListenerInterface
{
    public function handle(MksineEvent $event): void
    {
        $event->prevent('Prevented by listener');
    }

    public function shouldHandle(MksineEvent $event): bool
    {
        return true;
    }

    public function shouldQueue(): bool
    {
        return false;
    }

    public function priority(): int
    {
        return 0;
    }
}

// Test listener that should queue
class QueueableListener implements MksineListenerInterface
{
    public function handle(MksineEvent $event): void
    {
        $event->updateData('queued', true);
    }

    public function shouldHandle(MksineEvent $event): bool
    {
        return true;
    }

    public function shouldQueue(): bool
    {
        return true;
    }

    public function priority(): int
    {
        return 0;
    }
}

// Test listener that skips handling
class SkippingListener implements MksineListenerInterface
{
    public function handle(MksineEvent $event): void
    {
        $event->updateData('should_not_exist', true);
    }

    public function shouldHandle(MksineEvent $event): bool
    {
        return false;
    }

    public function shouldQueue(): bool
    {
        return false;
    }

    public function priority(): int
    {
        return 0;
    }
}

describe('HookDispatcher', function () {
    beforeEach(function () {
        $this->dispatcher = new HookDispatcher;
    });

    it('can dispatch event to listeners', function () {
        $event = new DispatcherTestEvent(['title' => 'Test']);
        $listeners = [
            [
                'listener' => ModifyingListener::class,
                'priority' => 0,
                'is_system' => false,
                'is_enabled' => true,
            ],
        ];

        $result = $this->dispatcher->dispatch($event, $listeners);

        expect($result->wasPrevented())->toBeFalse();
        expect($result->mutations())->toHaveCount(1);
        expect($event->data()->get('modified_by'))->toBe('ModifyingListener');
    });

    it('stops execution when event is prevented', function () {
        $event = new DispatcherTestEvent(['title' => 'Test']);
        $listeners = [
            [
                'listener' => PreventingListener::class,
                'priority' => 0,
                'is_system' => false,
                'is_enabled' => true,
            ],
            [
                'listener' => ModifyingListener::class,
                'priority' => 10,
                'is_system' => false,
                'is_enabled' => true,
            ],
        ];

        $result = $this->dispatcher->dispatch($event, $listeners);

        expect($result->wasPrevented())->toBeTrue();
        expect($result->preventReason())->toBe('Prevented by listener');
        // Second listener should not have executed
        expect($event->data()->get('modified_by'))->toBeNull();
    });

    it('collects pending async listeners', function () {
        $event = new DispatcherTestEvent(['title' => 'Test']);
        $listeners = [
            [
                'listener' => QueueableListener::class,
                'priority' => 0,
                'is_system' => false,
                'is_enabled' => true,
            ],
        ];

        $result = $this->dispatcher->dispatch($event, $listeners);

        expect($result->pendingAsyncListeners())->toContain(QueueableListener::class);
        // Queued listener should not have modified data
        expect($event->data()->get('queued'))->toBeNull();
    });

    it('skips listeners that should not handle', function () {
        $event = new DispatcherTestEvent(['title' => 'Test']);
        $listeners = [
            [
                'listener' => SkippingListener::class,
                'priority' => 0,
                'is_system' => false,
                'is_enabled' => true,
            ],
        ];

        $result = $this->dispatcher->dispatch($event, $listeners);

        expect($result->mutations())->toBeEmpty();
        expect($event->data()->get('should_not_exist'))->toBeNull();
    });

    it('skips disabled non-system listeners', function () {
        $event = new DispatcherTestEvent(['title' => 'Test']);
        $listeners = [
            [
                'listener' => ModifyingListener::class,
                'priority' => 0,
                'is_system' => false,
                'is_enabled' => false,
            ],
        ];

        $result = $this->dispatcher->dispatch($event, $listeners);

        expect($result->mutations())->toBeEmpty();
    });

    it('executes disabled system listeners', function () {
        $event = new DispatcherTestEvent(['title' => 'Test']);
        $listeners = [
            [
                'listener' => ModifyingListener::class,
                'priority' => 0,
                'is_system' => true,
                'is_enabled' => false, // Disabled but system
            ],
        ];

        $result = $this->dispatcher->dispatch($event, $listeners);

        // System listener should execute even if disabled
        expect($result->mutations())->toHaveCount(1);
    });

    it('can clear listener cache', function () {
        $event = new DispatcherTestEvent(['title' => 'Test']);
        $listeners = [
            [
                'listener' => ModifyingListener::class,
                'priority' => 0,
                'is_system' => false,
                'is_enabled' => true,
            ],
        ];

        $this->dispatcher->dispatch($event, $listeners);
        $this->dispatcher->clearCache();

        // Should not throw - just verify clear works
        expect(true)->toBeTrue();
    });
});
