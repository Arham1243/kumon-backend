<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('admin_companies')
            ->where('daily_summary_report_send_time', '17:00')
            ->update(['daily_summary_report_send_time' => '20:00']);

        Schema::table('admin_companies', function (Blueprint $table) {
            $table->string('daily_summary_report_send_time', 5)
                ->default('20:00')
                ->change();
        });
    }

    public function down(): void
    {
        DB::table('admin_companies')
            ->where('daily_summary_report_send_time', '20:00')
            ->update(['daily_summary_report_send_time' => '17:00']);

        Schema::table('admin_companies', function (Blueprint $table) {
            $table->string('daily_summary_report_send_time', 5)
                ->default('17:00')
                ->change();
        });
    }
};
