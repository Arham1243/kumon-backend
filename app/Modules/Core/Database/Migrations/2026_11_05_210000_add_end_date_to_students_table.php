<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('students', 'end_date')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            $table->date('end_date')->nullable()->after('start_date');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('students', 'end_date')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('end_date');
        });
    }
};
