<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\KeyController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Admin\SearchController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Customer\HomeController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuickCodeController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('landing');

// Hızlı onay: hesap açmadan anahtarla kod alma
Route::get('/onay', [QuickCodeController::class, 'show'])->name('quick');
Route::post('/onay', [QuickCodeController::class, 'open'])->middleware('throttle:redeem')->name('quick.open');
Route::post('/onay/cik', [QuickCodeController::class, 'close'])->name('quick.close');
Route::post('/onay/kod/{type}', [QuickCodeController::class, 'code'])->middleware('throttle:guard-code')->name('quick.code');

Route::middleware('auth')->group(function () {
    Route::get('/hesabim', [ProfileController::class, 'show'])->name('profile');

    // Müşteri
    Route::get('/kodlarim', [HomeController::class, 'index'])->name('home');

    Route::middleware('role:customer')->group(function () {
        Route::post('/anahtar', [HomeController::class, 'redeem'])->middleware('throttle:redeem')->name('redeem');
        Route::post('/kod/{key}/{account}/{type}', [HomeController::class, 'code'])->withTrashed()->middleware('throttle:guard-code')->name('code');
        Route::post('/paket/{key}/sec', [HomeController::class, 'pick'])->middleware('throttle:guard-code')->name('pick');
    });

    // Yönetim (mağaza sahibi)
    Route::middleware('role:admin')->prefix('yonetim')->name('admin.')->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('/ara', SearchController::class)->name('search');

        Route::get('/hesaplar', [AccountController::class, 'index'])->name('accounts.index');
        Route::get('/hesaplar/yeni', [AccountController::class, 'create'])->name('accounts.create');
        Route::post('/hesaplar', [AccountController::class, 'store'])->name('accounts.store');
        Route::post('/hesaplar/toplu', [AccountController::class, 'bulk'])->name('accounts.bulk');
        Route::get('/hesaplar/{account}/duzenle', [AccountController::class, 'edit'])->name('accounts.edit');
        Route::put('/hesaplar/{account}', [AccountController::class, 'update'])->name('accounts.update');
        Route::delete('/hesaplar/{account}', [AccountController::class, 'destroy'])->name('accounts.destroy');
        Route::post('/hesaplar/{account}/dene', [AccountController::class, 'test'])->middleware('throttle:6,1')->name('accounts.test');

        Route::get('/paketler', [PackageController::class, 'index'])->name('packages.index');
        Route::get('/paketler/yeni', [PackageController::class, 'create'])->name('packages.create');
        Route::post('/paketler', [PackageController::class, 'store'])->name('packages.store');
        Route::get('/paketler/{package}/duzenle', [PackageController::class, 'edit'])->name('packages.edit');
        Route::put('/paketler/{package}', [PackageController::class, 'update'])->name('packages.update');
        Route::delete('/paketler/{package}', [PackageController::class, 'destroy'])->name('packages.destroy');

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
