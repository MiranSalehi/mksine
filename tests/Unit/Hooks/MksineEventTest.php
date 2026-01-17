<?php

declare(strict_types=1);

use Miran\Mksine\Core\Events\MksineEvent;

// Create a concrete test event class
class TestPreventableEvent extends MksineEvent
{
    public function name(): string
    {
        return 'test.preventable';
    }

    public function canBePrevented(): bool
    {
        return true;
    }
}

class TestNonPreventableEvent extends MksineEvent
{
    public function name(): string
    {
        return 'test.non-preventable';
    }

    public function canBePrevented(): bool
    {
        return false;
    }
}

class TestAsyncEvent extends MksineEvent
{
    public function name(): string
    {
        return 'test.async';
    }

    public function canBePrevented(): bool
    {
        return false;
    }

    protected function allowAsync(): bool
    {
        return true;
    }
}

describe('MksineEvent', function () {
    it('provides access to data through data bag', function () {
        $event = new TestPreventableEvent(['title' => 'Test', 'slug' => 'test']);

        expect($event->data()->get('title'))->toBe('Test');
        expect($event->data()->get('slug'))->toBe('test');
        expect($event->data()->all())->toBe(['title' => 'Test', 'slug' => 'test']);
    });

    it('provides access to context', function () {
        $context = ['user_id' => 1, 'ip' => '127.0.0.1'];
        $event = new TestPreventableEvent(['title' => 'Test'], $context);

        expect($event->context())->toBe($context);
    });

    it('can update data and track mutations', function () {
        $event = new TestPreventableEvent(['title' => 'Original']);

        $event->updateData('title', 'Updated');
        $event->updateData('slug', 'updated-slug');

        expect($event->data()->get('title'))->toBe('Updated');
        expect($event->data()->get('slug'))->toBe('updated-slug');

        $mutations = $event->mutations();
        expect($mutations)->toHaveCount(2);
        expect($mutations[0])->toBe([
            'key' => 'title',
            'old' => 'Original',
            'new' => 'Updated',
        ]);
        expect($mutations[1])->toBe([
            'key' => 'slug',
            'old' => null,
            'new' => 'updated-slug',
        ]);
    });

    it('can prevent event with reason', function () {
        $event = new TestPreventableEvent(['title' => 'Test']);

        expect($event->isPrevented())->toBeFalse();
        expect($event->preventReason())->toBeNull();

        $event->prevent('Validation failed');

        expect($event->isPrevented())->toBeTrue();
        expect($event->preventReason())->toBe('Validation failed');
    });

    it('throws exception when preventing non-preventable event', function () {
        $event = new TestNonPreventableEvent(['title' => 'Test']);

        expect(fn () => $event->prevent('Should fail'))
            ->toThrow(\RuntimeException::class, 'Event cannot be prevented');
    });

    it('throws exception when updating data on prevented event', function () {
        $event = new TestPreventableEvent(['title' => 'Test']);
        $event->prevent('Prevented');

        expect(fn () => $event->updateData('title', 'New'))
            ->toThrow(\RuntimeException::class, 'Cannot update data on a prevented event');
    });

    it('correctly reports async allowed status', function () {
        $syncEvent = new TestPreventableEvent(['title' => 'Test']);
        $asyncEvent = new TestAsyncEvent(['title' => 'Test']);

        expect($syncEvent->isAsyncAllowed())->toBeFalse();
        expect($asyncEvent->isAsyncAllowed())->toBeTrue();
    });

    it('can revert mutations to specific count', function () {
        $event = new TestPreventableEvent(['title' => 'Original', 'slug' => 'original']);

        $event->updateData('title', 'Update 1');
        $event->updateData('slug', 'update-1');
        $event->updateData('content', 'New content');

        expect($event->mutations())->toHaveCount(3);

        $event->revertMutationsToCount(1);

        expect($event->mutations())->toHaveCount(1);
        expect($event->mutations()[0]['key'])->toBe('title');
    });

    it('returns all data including mutations', function () {
        $event = new TestPreventableEvent(['title' => 'Original']);
        $event->updateData('slug', 'new-slug');

        $allData = $event->allData();

        expect($allData)->toBe([
            'title' => 'Original',
            'slug' => 'new-slug',
        ]);
    });
});
