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
Route::get('/oyunlar', App\Http\Controllers\GameCatalogController::class)->name('games');

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
        Route::post('/kart/{account}/ad', [HomeController::class, 'label'])->withTrashed()->middleware('throttle:30,1')->name('label');
        Route::post('/hak-iste/{key}/{account}', [HomeController::class, 'requestRight'])->middleware('throttle:6,1')->name('rights.request');
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
        Route::post('/hesaplar/{account}/telafi', [AccountController::class, 'compensate'])->name('accounts.compensate');
        Route::post('/hesaplar/{account}/tasi', [AccountController::class, 'move'])->name('accounts.move');

        Route::get('/oyunlar', [App\Http\Controllers\Admin\GameController::class, 'index'])->name('games.index');
        Route::post('/oyunlar', [App\Http\Controllers\Admin\GameController::class, 'store'])->name('games.store');
        Route::post('/oyunlar/liste', [App\Http\Controllers\Admin\GameController::class, 'bulk'])->name('games.bulk');
        Route::get('/oyunlar/oneri', [App\Http\Controllers\Admin\GameController::class, 'suggest'])->middleware('throttle:90,1')->name('games.suggest');
        Route::post('/oyunlar/tamamla', [App\Http\Controllers\Admin\GameController::class, 'fill'])->middleware('throttle:6,1')->name('games.fill');
        Route::post('/oyunlar/{game}/kapak', [App\Http\Controllers\Admin\GameController::class, 'findCover'])->middleware('throttle:30,1')->name('games.cover');
        Route::get('/oyunlar/{game}/duzenle', [App\Http\Controllers\Admin\GameController::class, 'edit'])->name('games.edit');
        Route::put('/oyunlar/{game}', [App\Http\Controllers\Admin\GameController::class, 'update'])->name('games.update');
        Route::patch('/oyunlar/{game}/degistir', [App\Http\Controllers\Admin\GameController::class, 'toggle'])->name('games.toggle');
        Route::delete('/oyunlar/{game}', [App\Http\Controllers\Admin\GameController::class, 'destroy'])->name('games.destroy');

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
        Route::patch('/anahtarlar/{key}/ek-hak', [KeyController::class, 'bonus'])->name('keys.bonus');
        Route::patch('/anahtarlar/{key}/uzat', [KeyController::class, 'extend'])->name('keys.extend');

        Route::get('/musteriler', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('/musteriler/{user}', [CustomerController::class, 'show'])->name('customers.show');
        Route::patch('/istekler/{rightRequest}', [CustomerController::class, 'handleRequest'])->name('requests.handle');
        Route::patch('/musteriler/{user}/durum', [CustomerController::class, 'toggle'])->name('customers.toggle');

        Route::get('/kayitlar', [LogController::class, 'index'])->name('logs');

        Route::get('/ayarlar/{tab?}', [SettingsController::class, 'edit'])->name('settings');
        Route::post('/ayarlar/{tab}', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('/ayarlar-bildirim-dene', [SettingsController::class, 'testNotify'])->middleware('throttle:5,1')->name('settings.notify-test');
    });
});
