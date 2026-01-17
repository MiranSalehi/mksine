<?php

declare(strict_types=1);

use Miran\Mksine\Models\Category;

describe('Category Model', function () {
    it('has correct fillable attributes', function () {
        $category = new Category;
        $fillable = $category->getFillable();

        expect($fillable)->toContain('name');
        expect($fillable)->toContain('slug');
        expect($fillable)->toContain('description');
        expect($fillable)->toContain('image');
        expect($fillable)->toContain('parent_id');
        expect($fillable)->toContain('sort_order');
        expect($fillable)->toContain('is_active');
        expect($fillable)->toContain('meta_title');
        expect($fillable)->toContain('meta_description');
    });

    it('casts is_active to boolean', function () {
        $category = new Category;
        $casts = $category->getCasts();

        expect($casts['is_active'])->toBe('boolean');
    });

    it('casts sort_order to integer', function () {
        $category = new Category;
        $casts = $category->getCasts();

        expect($casts['sort_order'])->toBe('integer');
    });

    it('uses soft deletes', function () {
        $category = new Category;

        expect(in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($category)))->toBeTrue();
    });

    it('has parent relationship', function () {
        $category = new Category;
        $relation = $category->parent();

        expect($relation)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    });

    it('has children relationship', function () {
        $category = new Category;
        $relation = $category->children();

        expect($relation)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
    });

    it('has posts relationship', function () {
        $category = new Category;
        $relation = $category->posts();

        expect($relation)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
    });
});
