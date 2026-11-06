<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('students')) {
            return;
        }

        if (! Schema::hasColumn('students', 'card_assigned_at')) {
            Schema::table('students', function (Blueprint $table) {
                $table->timestamp('card_assigned_at')->nullable()->after('card_uid');
            });
        }

        if (Schema::hasColumn('students', 'card_assigned_at')) {
            DB::table('students')
                ->whereNotNull('card_uid')
                ->whereNull('card_assigned_at')
                ->update(['card_assigned_at' => DB::raw('updated_at')]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('students')) {
            return;
        }

        if (Schema::hasColumn('students', 'card_assigned_at')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('card_assigned_at');
            });
        }
    }
};
