<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('card_uid', 32)->nullable()->after('name');
            $table->unique(['company_id', 'card_uid']);
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'card_uid']);
            $table->dropColumn('card_uid');
        });
    }
};
