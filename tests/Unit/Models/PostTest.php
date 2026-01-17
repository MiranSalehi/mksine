<?php

declare(strict_types=1);

use Miran\Mksine\Models\Post;

describe('Post Model', function () {
    it('has correct fillable attributes', function () {
        $post = new Post;
        $fillable = $post->getFillable();

        expect($fillable)->toContain('title');
        expect($fillable)->toContain('slug');
        expect($fillable)->toContain('content');
        expect($fillable)->toContain('excerpt');
        expect($fillable)->toContain('status');
        expect($fillable)->toContain('featured_image');
        expect($fillable)->toContain('author_id');
        expect($fillable)->toContain('published_at');
        expect($fillable)->toContain('meta_title');
        expect($fillable)->toContain('meta_description');
        expect($fillable)->toContain('views_count');
    });

    it('casts published_at to datetime', function () {
        $post = new Post;
        $casts = $post->getCasts();

        expect($casts['published_at'])->toBe('datetime');
    });

    it('casts views_count to integer', function () {
        $post = new Post;
        $casts = $post->getCasts();

        expect($casts['views_count'])->toBe('integer');
    });

    it('uses soft deletes', function () {
        $post = new Post;

        expect(in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($post)))->toBeTrue();
    });

    it('has author relationship', function () {
        $post = new Post;
        $relation = $post->author();

        expect($relation)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    });

    it('has categories relationship', function () {
        $post = new Post;
        $relation = $post->categories();

        expect($relation)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
    });
});
