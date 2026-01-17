<?php

declare(strict_types=1);

use Miran\Mksine\Core\Hooks\HookRegistry;

beforeEach(function () {
    $this->registry = new HookRegistry;
});

describe('HookRegistry', function () {
    it('can register a listener for an event', function () {
        $this->registry->register('post.creating', 'App\Listeners\TestListener', 10);

        $listeners = $this->registry->getListenersForEvent('post.creating');

        expect($listeners)->toHaveCount(1);
        expect($listeners[0]['listener'])->toBe('App\Listeners\TestListener');
        expect($listeners[0]['priority'])->toBe(10);
    });

    it('can register multiple listeners for same event', function () {
        $this->registry->register('post.creating', 'App\Listeners\ListenerA', 10);
        $this->registry->register('post.creating', 'App\Listeners\ListenerB', 20);

        $listeners = $this->registry->getListenersForEvent('post.creating');

        expect($listeners)->toHaveCount(2);
    });

    it('returns empty array for non-existent event', function () {
        $listeners = $this->registry->getListenersForEvent('non.existent');

        expect($listeners)->toBeArray();
        expect($listeners)->toBeEmpty();
    });

    it('can get all registered listeners', function () {
        $this->registry->register('post.creating', 'App\Listeners\ListenerA', 10);
        $this->registry->register('post.created', 'App\Listeners\ListenerB', 20);

        $all = $this->registry->all();

        expect($all)->toHaveKey('post.creating');
        expect($all)->toHaveKey('post.created');
    });

    it('uses default priority of 0', function () {
        $this->registry->register('post.creating', 'App\Listeners\TestListener');

        $listeners = $this->registry->getListenersForEvent('post.creating');

        expect($listeners[0]['priority'])->toBe(0);
    });

    it('preserves registration order for listeners with same event', function () {
        $this->registry->register('post.creating', 'App\Listeners\First', 0);
        $this->registry->register('post.creating', 'App\Listeners\Second', 0);
        $this->registry->register('post.creating', 'App\Listeners\Third', 0);

        $listeners = $this->registry->getListenersForEvent('post.creating');

        expect($listeners[0]['listener'])->toBe('App\Listeners\First');
        expect($listeners[1]['listener'])->toBe('App\Listeners\Second');
        expect($listeners[2]['listener'])->toBe('App\Listeners\Third');
    });

    it('can check if listener exists', function () {
        $this->registry->register('post.creating', 'App\Listeners\TestListener', 10);

        expect($this->registry->hasListener('App\Listeners\TestListener'))->toBeTrue();
        expect($this->registry->hasListener('App\Listeners\NonExistent'))->toBeFalse();
    });

    it('can clear all listeners', function () {
        $this->registry->register('post.creating', 'App\Listeners\TestListener', 10);
        $this->registry->register('post.created', 'App\Listeners\AnotherListener', 20);

        $this->registry->clear();

        expect($this->registry->all())->toBeEmpty();
        expect($this->registry->hasListener('App\Listeners\TestListener'))->toBeFalse();
    });
});
