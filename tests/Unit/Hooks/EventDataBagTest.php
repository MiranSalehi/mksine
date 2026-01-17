<?php

declare(strict_types=1);

use Miran\Mksine\Core\Events\EventDataBag;

describe('EventDataBag', function () {
    it('can get data by key', function () {
        $bag = new EventDataBag(['title' => 'Test Title', 'slug' => 'test-slug']);

        expect($bag->get('title'))->toBe('Test Title');
        expect($bag->get('slug'))->toBe('test-slug');
    });

    it('returns default for non-existent key', function () {
        $bag = new EventDataBag(['title' => 'Test']);

        expect($bag->get('missing'))->toBeNull();
        expect($bag->get('missing', 'default'))->toBe('default');
    });

    it('can check if key exists', function () {
        $bag = new EventDataBag(['title' => 'Test', 'nullable' => null]);

        expect($bag->has('title'))->toBeTrue();
        expect($bag->has('nullable'))->toBeTrue();
        expect($bag->has('missing'))->toBeFalse();
    });

    it('can get all data', function () {
        $data = ['title' => 'Test', 'slug' => 'test'];
        $bag = new EventDataBag($data);

        expect($bag->all())->toBe($data);
    });

    it('is immutable - cannot modify data', function () {
        $data = ['title' => 'Original'];
        $bag = new EventDataBag($data);

        $all = $bag->all();
        $all['title'] = 'Modified';

        // Original bag should be unaffected
        expect($bag->get('title'))->toBe('Original');
    });

    it('handles nested data with dot notation', function () {
        $bag = new EventDataBag([
            'meta' => [
                'title' => 'Meta Title',
                'description' => 'Meta Description',
            ],
        ]);

        expect($bag->get('meta.title'))->toBe('Meta Title');
        expect($bag->get('meta.description'))->toBe('Meta Description');
    });

    it('handles empty data', function () {
        $bag = new EventDataBag([]);

        expect($bag->all())->toBe([]);
        expect($bag->get('any'))->toBeNull();
        expect($bag->has('any'))->toBeFalse();
    });
});
