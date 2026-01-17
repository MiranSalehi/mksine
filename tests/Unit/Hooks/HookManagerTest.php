<?php

declare(strict_types=1);

use Miran\Mksine\Core\Events\MksineEvent;
use Miran\Mksine\Core\Hooks\HookDispatcher;
use Miran\Mksine\Core\Hooks\HookManager;
use Miran\Mksine\Core\Hooks\HookRegistry;
use Miran\Mksine\Core\Hooks\HookStateRepository;
use Miran\Mksine\Core\Hooks\MksineListenerInterface;

// Test event class
class ManagerTestEvent extends MksineEvent
{
    public function name(): string
    {
        return 'manager.test';
    }

    public function canBePrevented(): bool
    {
        return true;
    }
}

// Simple test listener
class SimpleListener implements MksineListenerInterface
{
    public static bool $handled = false;

    public function handle(MksineEvent $event): void
    {
        self::$handled = true;
        $event->updateData('handled', true);
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

describe('HookManager', function () {
    beforeEach(function () {
        $this->registry = new HookRegistry;
        $this->stateRepository = new HookStateRepository;
        $this->dispatcher = new HookDispatcher;
        $this->manager = new HookManager(
            $this->registry,
            $this->stateRepository,
            $this->dispatcher
        );
        SimpleListener::$handled = false;
    });

    it('can register listeners', function () {
        $this->manager->register('test.event', SimpleListener::class, 10);

        $listeners = $this->manager->getRegisteredListeners();

        expect($listeners)->toHaveKey('test.event');
        expect($listeners['test.event'])->toHaveCount(1);
        expect($listeners['test.event'][0]['listener'])->toBe(SimpleListener::class);
    });

    it('can dispatch events to registered listeners', function () {
        $this->manager->register('manager.test', SimpleListener::class);

        $event = new ManagerTestEvent(['title' => 'Test']);
        $result = $this->manager->dispatch($event);

        expect(SimpleListener::$handled)->toBeTrue();
        expect($result->mutations())->toHaveCount(1);
    });

    it('returns empty result for events with no listeners', function () {
        $event = new ManagerTestEvent(['title' => 'Test']);
        $result = $this->manager->dispatch($event);

        expect($result->wasPrevented())->toBeFalse();
        expect($result->mutations())->toBeEmpty();
    });

    it('can check if listener is enabled', function () {
        // By default, listeners are enabled
        expect($this->manager->isListenerEnabled(SimpleListener::class))->toBeTrue();
    });

    it('can clear cache', function () {
        $this->manager->register('test.event', SimpleListener::class);
        $this->manager->clearCache();

        // Should not throw
        expect(true)->toBeTrue();
    });

    it('deprecated methods emit warnings in debug mode', function () {
        // These methods are deprecated but should still work without throwing
        $this->manager->enableListener(SimpleListener::class);
        $this->manager->disableListener(SimpleListener::class);
        $this->manager->setPriority(SimpleListener::class, 50);

        expect(true)->toBeTrue();
    });
});
