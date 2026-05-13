<?php

declare(strict_types=1);

use Miran\Mksine\Core\Updater\UpdateException;
use Miran\Mksine\Core\Updater\UpdateLock;
use Miran\Mksine\Core\Updater\UpdateTarget;

it('acquires and releases a per-target lock', function (): void {
    $lock = new UpdateLock(UpdateTarget::Plugin, 'demo-plugin');
    $lock->acquire();

    // Lock file must exist while held.
    expect(is_file(storage_path('app/mksine-updates/locks/plugin-demo-plugin.lock')))->toBeTrue();

    $lock->release();

    // After release the lock file is removed.
    expect(is_file(storage_path('app/mksine-updates/locks/plugin-demo-plugin.lock')))->toBeFalse();
});

it('rejects a second concurrent acquire for the same target', function (): void {
    $first = new UpdateLock(UpdateTarget::Plugin, 'concurrency-demo');
    $first->acquire();

    $second = new UpdateLock(UpdateTarget::Plugin, 'concurrency-demo');

    expect(fn () => $second->acquire())
        ->toThrow(UpdateException::class, 'Another update is already running');

    $first->release();
});

it('allows independent locks on different targets simultaneously', function (): void {
    $a = new UpdateLock(UpdateTarget::Plugin, 'alpha');
    $b = new UpdateLock(UpdateTarget::Plugin, 'beta');
    $c = new UpdateLock(UpdateTarget::Theme, 'alpha'); // same id, different target

    $a->acquire();
    $b->acquire();
    $c->acquire();

    expect(true)->toBeTrue(); // no exceptions

    $a->release();
    $b->release();
    $c->release();
});

it('releases automatically via destructor', function (): void {
    $lock = new UpdateLock(UpdateTarget::Core, 'mksine-core');
    $lock->acquire();
    unset($lock);

    // A new lock on the same target should succeed now.
    $fresh = new UpdateLock(UpdateTarget::Core, 'mksine-core');
    $fresh->acquire();
    $fresh->release();

    expect(true)->toBeTrue();
});
