<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ArticleController::class, 'index'])->name('home');
Route::get('/category/{category?}', [ArticleController::class, 'category'])->name('category');

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('register.store');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('role:Журналист,Администратор')->group(function (): void {
        Route::get('/journalist', [ArticleController::class, 'create'])->name('journalist');
        Route::post('/articles', [ArticleController::class, 'store'])->name('articles.store');
        Route::get('/journalist/{article}/edit', [ArticleController::class, 'edit'])->name('articles.edit');
        Route::put('/articles/{article}', [ArticleController::class, 'update'])->name('articles.update');
    });

    Route::middleware('role:Администратор')->group(function (): void {
        Route::get('/admin', [AdminController::class, 'index'])->name('admin');
        Route::delete('/admin/articles/{article}', [AdminController::class, 'destroy'])->name('admin.articles.destroy');
        Route::patch('/admin/articles/{article}/block', [AdminController::class, 'setBlocked'])->name('admin.articles.block');
        Route::patch('/admin/users/role', [AdminController::class, 'updateRole'])->name('admin.users.role');
    });
});
