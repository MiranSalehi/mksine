<?php

use Illuminate\Support\Facades\Route;
use Miran\Mksine\Livewire\Frontend\Home;
use Miran\Mksine\Livewire\Frontend\CategoryList;
use Miran\Mksine\Livewire\Frontend\CategoryShow;
use Miran\Mksine\Livewire\Frontend\PostList;
use Miran\Mksine\Livewire\Frontend\PostShow;

Route::middleware(['web'])->group(function () {
    Route::get('/', Home::class)->name('home');
    Route::get('/categories', CategoryList::class)->name('categories.index');
    Route::get('/category/{slug}', CategoryShow::class)->name('categories.show');
    Route::get('/posts', PostList::class)->name('posts.index');
    Route::get('/post/{slug}', PostShow::class)->name('posts.show');
});
