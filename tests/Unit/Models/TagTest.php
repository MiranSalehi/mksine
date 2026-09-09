<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Miran\Mksine\Models\Page;
use Miran\Mksine\Models\Post;
use Miran\Mksine\Models\Tag;

describe('Tag Model', function () {
    it('has correct fillable attributes', function () {
        $tag = new Tag;
        $fillable = $tag->getFillable();

        expect($fillable)->toContain('name');
        expect($fillable)->toContain('slug');
        expect($fillable)->toContain('description');
        expect($fillable)->toContain('is_active');
        expect($fillable)->toContain('meta_title');
        expect($fillable)->toContain('meta_description');
        expect($fillable)->not->toContain('parent_id');
    });

    it('casts is_active to boolean', function () {
        $tag = new Tag;
        $casts = $tag->getCasts();

        expect($casts['is_active'])->toBe('boolean');
    });

    it('uses soft deletes', function () {
        $tag = new Tag;

        expect(in_array(SoftDeletes::class, class_uses_recursive($tag)))->toBeTrue();
    });

    it('has posts morph relationship', function () {
        $tag = new Tag;
        $relation = $tag->posts();

        expect($relation)->toBeInstanceOf(MorphToMany::class);
    });

    it('has pages morph relationship', function () {
        $tag = new Tag;
        $relation = $tag->pages();

        expect($relation)->toBeInstanceOf(MorphToMany::class);
    });
});

describe('HasTags', function () {
    it('exposes morphToMany tags on Post', function () {
        $post = new Post;
        $relation = $post->tags();

        expect($relation)->toBeInstanceOf(MorphToMany::class);
    });

    it('exposes morphToMany tags on Page', function () {
        $page = new Page;
        $relation = $page->tags();

        expect($relation)->toBeInstanceOf(MorphToMany::class);
    });
});
