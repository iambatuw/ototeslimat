<?php

namespace App\Support;

use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\ProductKey;
use Illuminate\Support\Str;

class Activity
{
    /**
     * @param  array{user_id?: ?int, key?: ?ProductKey, account?: ?Account, meta?: array}  $context
     */
    public static function log(string $event, array $context = []): ActivityLog
    {
        $request = request();
        $key = $context['key'] ?? null;
        $account = $context['account'] ?? ($key?->account);

        return ActivityLog::create([
            'event' => $event,
            'user_id' => array_key_exists('user_id', $context) ? $context['user_id'] : $request?->user()?->id,
            'product_key_id' => $key?->id,
            'account_id' => $account?->id,
            'ip' => $request?->ip(),
            'user_agent' => Str::limit((string) $request?->userAgent(), 250, ''),
            'meta' => $context['meta'] ?? null,
        ]);
    }
}
