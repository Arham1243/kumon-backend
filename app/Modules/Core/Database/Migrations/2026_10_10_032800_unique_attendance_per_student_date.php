<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->unique(
                ['company_id', 'student_id', 'date'],
                'attendances_company_student_date_unique'
            );
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'student_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->index(['company_id', 'student_id', 'date']);
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropUnique('attendances_company_student_date_unique');
        });
    }
};
