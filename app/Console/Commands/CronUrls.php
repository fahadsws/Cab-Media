<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\Automation;
use Illuminate\Console\Command;

class CronUrls extends Command
{
    protected $signature = 'cron:urls';
    protected $description = 'Print the cron URLs and ready-to-paste cPanel cron lines (with the real secret token)';

    public function handle(): int
    {
        $token = (string) Setting::get('cron.token', '');
        if ($token === '') { $this->error('No cron token yet: open Admin -> Automation once, or regenerate it there.'); return self::FAILURE; }

        $base = rtrim((string) config('app.url'), '/');
        $this->info('All tasks that are due (one job every 5 minutes is enough):');
        $this->line("*/5 * * * *  curl -fsS -m 20 \"$base/cron/$token\" >/dev/null 2>&1");
        $this->newLine();
        $this->info('Or one job per task:');
        foreach (Automation::TASKS as $task => [$label]) {
            $this->line(sprintf('%-14s curl -fsS -m 20 "%s/cron/%s/%s" >/dev/null 2>&1   # %s', Automation::cronExpr($task), $base, $token, $task, $label));
        }
        return self::SUCCESS;
    }
}
