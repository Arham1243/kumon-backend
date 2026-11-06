<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_companies', function (Blueprint $table) {
            $table->string('daily_summary_report_send_time', 5)
                ->default('20:00')
                ->after('date_format');
        });
    }

    public function down(): void
    {
        Schema::table('admin_companies', function (Blueprint $table) {
            $table->dropColumn('daily_summary_report_send_time');
        });
    }
};
