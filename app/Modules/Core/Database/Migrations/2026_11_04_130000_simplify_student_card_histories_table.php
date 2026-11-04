<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('student_card_histories')) {
            return;
        }

        Schema::table('student_card_histories', function (Blueprint $table) {
            if (Schema::hasColumn('student_card_histories', 'assigned_by')) {
                $table->dropForeign(['assigned_by']);
            }
            if (Schema::hasColumn('student_card_histories', 'unassigned_by')) {
                $table->dropForeign(['unassigned_by']);
            }
        });

        Schema::table('student_card_histories', function (Blueprint $table) {
            $columns = ['unassigned_at', 'assigned_by', 'unassigned_by', 'end_reason'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('student_card_histories', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('student_card_histories')) {
            return;
        }

        Schema::table('student_card_histories', function (Blueprint $table) {
            if (! Schema::hasColumn('student_card_histories', 'unassigned_at')) {
                $table->timestamp('unassigned_at')->nullable();
            }
            if (! Schema::hasColumn('student_card_histories', 'assigned_by')) {
                $table->foreignId('assigned_by')->nullable()->constrained('admin_users')->nullOnDelete();
            }
            if (! Schema::hasColumn('student_card_histories', 'unassigned_by')) {
                $table->foreignId('unassigned_by')->nullable()->constrained('admin_users')->nullOnDelete();
            }
            if (! Schema::hasColumn('student_card_histories', 'end_reason')) {
                $table->string('end_reason')->nullable();
            }
        });
    }
};
