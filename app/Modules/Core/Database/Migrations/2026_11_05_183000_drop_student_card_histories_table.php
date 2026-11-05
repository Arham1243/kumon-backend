<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('student_card_histories');

        if (Schema::hasTable('migrations')) {
            DB::table('migrations')
                ->where('migration', '2026_11_04_130000_simplify_student_card_histories_table')
                ->delete();
        }

        if (! Schema::hasTable('admin_permissions')) {
            return;
        }

        $ids = DB::table('admin_permissions')
            ->where('guard_name', 'sanctum')
            ->where('name', 'like', 'reports.student-cards.%')
            ->pluck('id');

        if ($ids->isNotEmpty()) {
            if (Schema::hasTable('admin_role_has_permissions')) {
                DB::table('admin_role_has_permissions')->whereIn('permission_id', $ids)->delete();
            }

            if (Schema::hasTable('admin_model_has_permissions')) {
                DB::table('admin_model_has_permissions')->whereIn('permission_id', $ids)->delete();
            }

            DB::table('admin_permissions')->whereIn('id', $ids)->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (! Schema::hasTable('student_card_histories')) {
            Schema::create('student_card_histories', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('company_id')->constrained('admin_companies')->cascadeOnDelete();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->string('card_uid', 32);
                $table->timestamp('assigned_at');
                $table->timestamps();

                $table->index(['company_id', 'student_id']);
                $table->index(['company_id', 'card_uid']);
            });
        }
    }
};
