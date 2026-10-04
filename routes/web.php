<?php

use App\Http\Controllers\Auth\ArticleController as AuthArticleController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\Auth\CategoryController;
use App\Http\Controllers\Auth\CustomerController;
use App\Http\Controllers\Auth\FAQController;
use App\Http\Controllers\Auth\DashboardController;
use App\Http\Controllers\Auth\ImageController;
use App\Http\Controllers\Auth\InternalCategoryController;
use App\Http\Controllers\Auth\KeywordController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\Auth\ContactController as AuthContactController;
use App\Http\Controllers\Auth\KeywordGroupController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SitemapController;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('/dashboard')->name('dashboard.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('index');
    Route::resource('/categories', CategoryController::class);
    Route::resource('/articles', AuthArticleController::class);
    Route::resource('/customers', CustomerController::class);
    Route::resource('/contacts', AuthContactController::class)->only(['index', 'show']);
    Route::resource('/internal-categories', InternalCategoryController::class);
    Route::resource('/keywords', KeywordController::class);
    Route::resource('/keyword-groups', KeywordGroupController::class);
    Route::resource('/images', ImageController::class)->except(['update']);
    Route::post('/images/{image}', [ImageController::class, 'update'])->name('images.update');
    Route::put('/images/{file}/restore', [ImageController::class, 'restore'])->name('images.restore');
    Route::resource('/faq', FAQController::class)->names('faqs');
    Route::put('/articles/{article}/restore', [AuthArticleController::class, 'restore'])->name('articles.restore');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
require __DIR__.'/api.php';

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

Route::middleware(SetLocale::class)->prefix('/{locale?}')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('welcome');
    Route::get('/articles', [ArticleController::class, 'index'])->name('articles.index');
    Route::get('/articles/{article:slug}', [ArticleController::class, 'show'])->name('articles.show');
});


