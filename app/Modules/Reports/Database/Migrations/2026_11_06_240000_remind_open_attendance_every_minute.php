<?php

use App\Modules\Reports\Models\ScheduledJob;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('admin_scheduled_jobs')
            ->where('key', 'remind-open-attendance')
            ->update([
                'frequency' => '* * * * *',
                'timezone' => null,
                'description' => 'Sends the daily attendance summary email per company at each company\'s configured send time in that company\'s timezone.',
                'updated_at' => now(),
            ]);

        ScheduledJob::flushCache();
    }

    public function down(): void
    {
        DB::table('admin_scheduled_jobs')
            ->where('key', 'remind-open-attendance')
            ->update([
                'frequency' => '0 20 * * *',
                'timezone' => 'America/New_York',
                'description' => 'Notifies each company super admin when students checked in today without checkout.',
                'updated_at' => now(),
            ]);

        ScheduledJob::flushCache();
    }
};
