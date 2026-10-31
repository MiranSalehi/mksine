<?php

declare(strict_types=1);

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Exceptions;
use Miran\Mksine\Core\Theme\ThemeActionManager;

function themeActionManagerTestCallback(): string
{
    return 'string-callable';
}

beforeEach(function (): void {
    $this->app->singleton(ThemeActionManager::class, fn () => new ThemeActionManager);
    $this->actions = app(ThemeActionManager::class);
});

test('lower priority runs first and returned and echoed output are concatenated', function (): void {
    $this->actions->addAction('t', fn (): string => 'b', 20);
    $this->actions->addAction('t', function (): string {
        echo 'a';

        return 'A';
    }, 10);

    expect($this->actions->doAction('t'))->toBe('aAb');
});

test('same-priority callbacks keep registration order', function (): void {
    $this->actions->addAction('t', fn (): string => 'a');
    $this->actions->addAction('t', fn (): string => 'b', 5);
    $this->actions->addAction('t', fn (): string => 'c');
    $this->actions->addAction('t', fn (): string => 'd', 10, 'named');
    $this->actions->addAction('t', fn (): string => 'e');

    expect($this->actions->doAction('t'))->toBe('bacde');
});

test('adding the same id twice leaves one callback, the newer one', function (): void {
    $this->actions->addAction('t', fn (): string => 'old', 10, 'x');
    $this->actions->addAction('t', fn (): string => 'new', 10, 'x');

    expect($this->actions->doAction('t'))->toBe('new')
        ->and($this->actions->inspect())->toBe([['name' => 't', 'callbacks' => 1, 'priorities' => [10]]]);
});

test('replacing an id keeps its place among same-priority callbacks', function (): void {
    $this->actions->addAction('t', fn (): string => 'a', 10, 'x');
    $this->actions->addAction('t', fn (): string => 'b');
    $this->actions->addAction('t', fn (): string => 'A', 10, 'x');

    expect($this->actions->doAction('t'))->toBe('Ab');
});

test('replacing an id applies the new priority', function (): void {
    $this->actions->addAction('t', fn (): string => 'a', 10, 'x');
    $this->actions->addAction('t', fn (): string => 'b', 20);
    $this->actions->addAction('t', fn (): string => 'A', 30, 'x');

    expect($this->actions->doAction('t'))->toBe('bA')
        ->and($this->actions->inspect()[0]['priorities'])->toBe([30, 20]);
});

test('remove by id', function (): void {
    $this->actions->addAction('t', fn (): string => 'a', 10, 'x');
    $this->actions->addAction('t', fn (): string => 'b');

    $this->actions->removeAction('t', 'x');

    expect($this->actions->doAction('t'))->toBe('b');
});

test('remove by closure reference', function (): void {
    $closure = fn (): string => 'a';
    $this->actions->addAction('t', $closure);
    $this->actions->addAction('t', $closure, 20);
    $this->actions->addAction('t', fn (): string => 'a');

    $this->actions->removeAction('t', $closure);

    expect($this->actions->inspect())->toBe([['name' => 't', 'callbacks' => 1, 'priorities' => [10]]]);
});

test('remove by string callable', function (): void {
    $this->actions->addAction('t', 'themeActionManagerTestCallback');
    $this->actions->addAction('t', fn (): string => 'b');

    expect($this->actions->doAction('t'))->toBe('string-callableb');

    $this->actions->removeAction('t', 'themeActionManagerTestCallback');

    expect($this->actions->doAction('t'))->toBe('b');
});

test('a string that matches an id leaves string callables alone', function (): void {
    $this->actions->addAction('t', 'themeActionManagerTestCallback');
    $this->actions->addAction('t', fn (): string => 'b', 10, 'themeActionManagerTestCallback');

    $this->actions->removeAction('t', 'themeActionManagerTestCallback');

    expect($this->actions->doAction('t'))->toBe('string-callable');
});

test('removing by id before the add blocks the later add', function (): void {
    $this->actions->removeAction('t', 'x');
    $this->actions->addAction('t', fn (): string => 'a', 10, 'x');

    expect($this->actions->doAction('t'))->toBe('')
        ->and($this->actions->hasAction('t'))->toBeFalse()
        ->and($this->actions->inspect())->toBe([]);
});

test('a blocked id does not block callbacks without an id', function (): void {
    $this->actions->removeAction('t', 'themeActionManagerTestCallback');
    $this->actions->addAction('t', 'themeActionManagerTestCallback');

    expect($this->actions->doAction('t'))->toBe('string-callable');
});

test('removing an unknown handler does nothing', function (): void {
    $this->actions->addAction('t', fn (): string => 'a');

    $this->actions->removeAction('t', 'missing');
    $this->actions->removeAction('t', fn (): string => 'a');
    $this->actions->removeAction('unknown', 'missing');

    expect($this->actions->doAction('t'))->toBe('a')
        ->and($this->actions->hasAction('unknown'))->toBeFalse()
        ->and($this->actions->inspect())->toHaveCount(1);
});

test('a removed handler on one hook does not affect another hook', function (): void {
    $closure = fn (): string => 'a';
    $this->actions->addAction('one', $closure, 10, 'x');
    $this->actions->addAction('two', $closure, 10, 'x');

    $this->actions->removeAction('one', 'x');
    $this->actions->addAction('three', $closure, 10, 'x');

    expect($this->actions->doAction('one'))->toBe('')
        ->and($this->actions->doAction('two'))->toBe('a')
        ->and($this->actions->doAction('three'))->toBe('a');
});

test('removing a callback keeps inspect priorities a list', function (): void {
    $this->actions->addAction('t', fn (): string => 'a', 1, 'x');
    $this->actions->addAction('t', fn (): string => 'b', 2);

    $this->actions->removeAction('t', 'x');

    expect($this->actions->inspect()[0]['priorities'])->toBe([2]);
});

test('removing the last callback clears the hook from hasAction and inspect', function (): void {
    $this->actions->addAction('t', fn (): string => 'a', 10, 'x');

    expect($this->actions->hasAction('t'))->toBeTrue();

    $this->actions->removeAction('t', 'x');

    expect($this->actions->hasAction('t'))->toBeFalse()
        ->and($this->actions->inspect())->toBe([]);
});

test('a failing action is reported, the other callbacks still run, and the partial output is dropped', function (): void {
    Exceptions::fake();
    config(['app.debug' => false]);

    $this->actions->addAction('t', fn (): string => 'before', 1);
    $this->actions->addAction('t', function (): never {
        echo 'partial';

        throw new RuntimeException('boom');
    }, 2);
    $this->actions->addAction('t', fn (): string => 'after', 3);

    expect($this->actions->doAction('t'))->toBe('beforeafter');

    Exceptions::assertReported(fn (RuntimeException $e): bool => $e->getMessage() === 'boom');
});

test('a failing action keeps the debug comment when app.debug is on', function (): void {
    Exceptions::fake();
    config(['app.debug' => true]);

    $this->actions->addAction('t', function (): never {
        echo 'partial';

        throw new RuntimeException('boom');
    });

    expect($this->actions->doAction('t'))->toBe('<!-- Theme hook error [t]: boom -->');
});

test('a failure while reporting does not break the page', function (): void {
    config(['app.debug' => true]);

    $handler = Mockery::mock(ExceptionHandler::class);
    $handler->shouldReceive('report')->once()->andThrow(new LogicException('log is down'));
    $this->app->instance(ExceptionHandler::class, $handler);

    $this->actions->addAction('t', function (): never {
        throw new RuntimeException('boom');
    }, 1);
    $this->actions->addAction('t', fn (): string => 'after', 2);

    expect($this->actions->doAction('t'))->toBe('<!-- Theme hook error [t]: boom -->after');
});

test('a callback with mismatched parameter names is reported', function (): void {
    Exceptions::fake();
    config(['app.debug' => false]);

    $this->actions->addAction('t', fn (int $productId): string => 'never');
    $this->actions->addAction('t', fn (): string => 'ignored-args');
    $this->actions->addAction('t', fn (int $variantId): string => "variant-{$variantId}");

    expect($this->actions->doAction('t', ['variantId' => 7]))->toBe('variant-7');

    Exceptions::assertReportedCount(2);
    Exceptions::assertReported(fn (Error $e): bool => str_contains($e->getMessage(), 'Unknown named parameter $variantId'));
});

test('the variadic pattern accepts extra arguments', function (): void {
    Exceptions::fake();

    $seen = null;
    $this->actions->addAction('t', function (int $variantId, mixed ...$context) use (&$seen): string {
        $seen = $context;

        return "variant-{$variantId}";
    });

    expect($this->actions->doAction('t', ['variantId' => 7, 'quantity' => 2]))->toBe('variant-7')
        ->and($seen)->toBe(['quantity' => 2]);

    Exceptions::assertNothingReported();
});

test('helpers forward to the manager', function (): void {
    theme_add_action('t', fn (): string => 'a', 10, 'x');
    theme_add_action('t', fn (): string => 'b', 20);

    expect(theme_do_action('t'))->toBe('ab');

    theme_remove_action('t', 'x');
    theme_add_action('t', fn (): string => 'c', 10, 'x');

    expect(theme_do_action('t'))->toBe('b');
});
