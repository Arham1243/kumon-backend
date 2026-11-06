<?php

use App\Modules\Reports\Models\ScheduledJob;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('admin_scheduled_jobs')
            ->where('key', 'auto-close-open-attendance')
            ->update([
                'frequency' => '* * * * *',
                'timezone' => null,
                'description' => 'For each company, sets checkout on still-open attendance rows at 23:00 on that company\'s calendar date, in that company\'s timezone.',
                'updated_at' => now(),
            ]);

        ScheduledJob::flushCache();
    }

    public function down(): void
    {
        DB::table('admin_scheduled_jobs')
            ->where('key', 'auto-close-open-attendance')
            ->update([
                'frequency' => '0 23 * * *',
                'timezone' => 'America/New_York',
                'description' => 'Sets checkout time for today’s open attendance rows at end of day.',
                'updated_at' => now(),
            ]);

        ScheduledJob::flushCache();
    }
};
