<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\KeyController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Customer\HomeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/hesabim', [ProfileController::class, 'show'])->name('profile');

    // Müşteri
    Route::get('/', [HomeController::class, 'index'])->name('home');

    Route::middleware('role:customer')->group(function () {
        Route::post('/anahtar', [HomeController::class, 'redeem'])->middleware('throttle:redeem')->name('redeem');
        Route::post('/kod/{key}/{type}', [HomeController::class, 'code'])->middleware('throttle:guard-code')->name('code');
    });

    // Yönetim (mağaza sahibi)
    Route::middleware('role:admin')->prefix('yonetim')->name('admin.')->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::get('/hesaplar', [AccountController::class, 'index'])->name('accounts.index');
        Route::get('/hesaplar/yeni', [AccountController::class, 'create'])->name('accounts.create');
        Route::post('/hesaplar', [AccountController::class, 'store'])->name('accounts.store');
        Route::post('/hesaplar/toplu', [AccountController::class, 'bulk'])->name('accounts.bulk');
        Route::get('/hesaplar/{account}/duzenle', [AccountController::class, 'edit'])->name('accounts.edit');
        Route::put('/hesaplar/{account}', [AccountController::class, 'update'])->name('accounts.update');
        Route::delete('/hesaplar/{account}', [AccountController::class, 'destroy'])->name('accounts.destroy');
        Route::post('/hesaplar/{account}/dene', [AccountController::class, 'test'])->middleware('throttle:6,1')->name('accounts.test');

        Route::get('/anahtarlar', [KeyController::class, 'index'])->name('keys.index');
        Route::post('/anahtarlar', [KeyController::class, 'store'])->name('keys.store');
        Route::get('/anahtarlar/disa-aktar', [KeyController::class, 'export'])->name('keys.export');
        Route::delete('/anahtarlar/temizle', [KeyController::class, 'purge'])->name('keys.purge');
        Route::patch('/anahtarlar/{key}/iptal', [KeyController::class, 'revoke'])->name('keys.revoke');
        Route::patch('/anahtarlar/{key}/ac', [KeyController::class, 'restore'])->name('keys.restore');

        Route::get('/musteriler', [CustomerController::class, 'index'])->name('customers.index');
        Route::patch('/musteriler/{user}/durum', [CustomerController::class, 'toggle'])->name('customers.toggle');

        Route::get('/kayitlar', [LogController::class, 'index'])->name('logs');

        Route::get('/ayarlar/{tab?}', [SettingsController::class, 'edit'])->name('settings');
        Route::post('/ayarlar/{tab}', [SettingsController::class, 'update'])->name('settings.update');
    });
});
