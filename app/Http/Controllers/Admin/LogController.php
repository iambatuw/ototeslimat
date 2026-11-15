<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Support\KeyCode;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::query()->with(['user:id,email', 'account:id,title,platform,deleted_at']);

        if (($event = $request->input('event')) && array_key_exists($event, ActivityLog::EVENTS)) {
            $query->where('event', $event);
        }

        if ($q = trim((string) $request->input('q'))) {
            if ($body = KeyCode::normalize($q)) {
                $query->whereHas('key', fn ($k) => $k->where('code_hash', KeyCode::hash($body)));
            } elseif (filter_var($q, FILTER_VALIDATE_IP)) {
                $query->where('ip', $q);
            } else {
                $query->whereHas('user', fn ($u) => $u->where('email', 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%'));
            }
        }

        return view('admin.logs', [
            'logs' => $query->latest('id')->paginate(50)->withQueryString(),
            'filters' => $request->only('event', 'q'),
        ]);
    }
}
