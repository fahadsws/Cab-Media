<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\Automation;

class CronController extends Controller
{
    /**
     * GET /cron/{token}          -> runs every task that is due
     * GET /cron/{token}/{task}   -> runs one task if its own interval has elapsed (news|publish|videos|reindex|cleanup)
     *
     * The response is sent immediately; the work runs afterwards in this PHP process, guarded by a lock, so the
     * public site is never held up and two pings can never overlap.
     */
    public function __invoke(string $token, ?string $task = null)
    {
        $expected = (string) Setting::get('cron.token', '');
        abort_unless($expected !== '' && hash_equals($expected, $token), 404);
        abort_if($task !== null && ! array_key_exists($task, Automation::TASKS), 404);

        ignore_user_abort(true);
        dispatch(function () use ($task) {
            try { Automation::run($task); } catch (\Throwable $e) { report($e); }
        })->afterResponse();

        return response()->json([
            'accepted' => true,
            'task' => $task ?? 'all-due',
            'ran_at' => now()->toIso8601String(),
        ]);
    }
}
