<?php

namespace App\Providers;

use App\Support\Activity;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());
        Carbon::setLocale('tr');

        if ($proxies = config('guard.trusted_proxies')) {
            TrustProxies::at($proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }

        Password::defaults(fn () => $this->app->isProduction()
            ? Password::min(10)->letters()->numbers()->uncompromised()
            : Password::min(8));

        $this->rateLimits();
        $this->authLogging();
    }

    private function rateLimits(): void
    {
        // Anahtar denemesi: kullanıcı ve IP ayrı ayrı sınırlanır, kaba kuvvet işe yaramaz.
        RateLimiter::for('redeem', function (Request $request) {
            $limit = config('guard.limits.redeem_per_minute');

            return [
                Limit::perMinute($limit)->by('u:'.$request->user()?->id),
                Limit::perMinute($limit * 3)->by('ip:'.$request->ip()),
                Limit::perHour($limit * 6)->by('uh:'.$request->user()?->id),
            ];
        });

        RateLimiter::for('guard-code', function (Request $request) {
            return Limit::perMinute(config('guard.limits.code_per_minute'))
                ->by('u:'.$request->user()?->id)
                ->response(fn () => response()->json([
                    'message' => 'Çok sık istek gönderdin. Bir dakika bekleyip tekrar dene.',
                ], 429));
        });
    }

    private function authLogging(): void
    {
        Event::listen(Login::class, function (Login $event) {
            $event->user->forceFill([
                'last_login_at' => now(),
                'last_login_ip' => request()->ip(),
            ])->saveQuietly();

            Activity::log('auth.login', ['user_id' => $event->user->id]);
        });

        Event::listen(Failed::class, function (Failed $event) {
            Activity::log('auth.failed', [
                'user_id' => $event->user?->id,
                'meta' => ['email' => mb_substr((string) ($event->credentials['email'] ?? ''), 0, 120)],
            ]);
        });

        Event::listen(Registered::class, function (Registered $event) {
            Activity::log('auth.registered', ['user_id' => $event->user->id]);
        });
    }
}
