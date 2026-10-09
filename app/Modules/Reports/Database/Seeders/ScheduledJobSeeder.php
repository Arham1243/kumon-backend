<?php

namespace App\Modules\Reports\Database\Seeders;

use App\Modules\Core\Http\Controllers\Cron\AutoCloseOpenAttendanceController;
use App\Modules\Core\Http\Controllers\Cron\RemindOpenAttendanceController;
use App\Modules\Reports\Http\Controllers\Cron\SyncEmailLogsController;
use App\Modules\Reports\Models\ScheduledJob;
use Illuminate\Database\Seeder;

class ScheduledJobSeeder extends Seeder
{
    public function run(): void
    {
        $jobs = [
            [
                'key' => 'sync-email-logs',
                'name' => 'Sync Email Logs',
                'description' => 'Pulls recent Mailgun events to keep admin portal email logs up to date.',
                'handler' => SyncEmailLogsController::class,
                'endpoint' => '/api/cron/sync-email-logs',
                'token_config_key' => 'services.mailgun_email_log_sync.token',
                'frequency' => '*/15 * * * *',
                'timezone' => null,
            ],
            [
                'key' => 'remind-open-attendance',
                'name' => 'Remind Open Attendance',
                'description' => 'Notifies each company super admin when students checked in today without checkout.',
                'handler' => RemindOpenAttendanceController::class,
                'endpoint' => '/api/cron/remind-open-attendance',
                'token_config_key' => 'services.attendance_cron.token',
                'frequency' => '0 20 * * *',
                'timezone' => 'America/New_York',
            ],
            [
                'key' => 'auto-close-open-attendance',
                'name' => 'Auto-Close Open Attendance',
                'description' => 'Sets checkout time for today’s open attendance rows at end of day.',
                'handler' => AutoCloseOpenAttendanceController::class,
                'endpoint' => '/api/cron/auto-close-open-attendance',
                'token_config_key' => 'services.attendance_cron.token',
                'frequency' => '0 23 * * *',
                'timezone' => 'America/New_York',
            ],
        ];

        foreach ($jobs as $job) {
            ScheduledJob::query()->updateOrCreate(
                ['key' => $job['key']],
                [
                    'name' => $job['name'],
                    'description' => $job['description'],
                    'handler' => $job['handler'],
                    'endpoint' => $job['endpoint'],
                    'token_config_key' => $job['token_config_key'],
                    'frequency' => $job['frequency'],
                    'timezone' => $job['timezone'] ?? null,
                    'enabled' => true,
                ]
            );
        }

        $managedKeys = array_column($jobs, 'key');
        ScheduledJob::query()
            ->whereNotIn('key', $managedKeys)
            ->delete();

        ScheduledJob::flushCache();
    }
}
