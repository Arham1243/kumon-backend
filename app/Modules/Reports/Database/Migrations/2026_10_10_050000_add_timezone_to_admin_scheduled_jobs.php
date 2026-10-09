<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_scheduled_jobs', function (Blueprint $table) {
            $table->string('timezone', 100)->nullable()->after('frequency');
        });
    }

    public function down(): void
    {
        Schema::table('admin_scheduled_jobs', function (Blueprint $table) {
            $table->dropColumn('timezone');
        });
    }
};
