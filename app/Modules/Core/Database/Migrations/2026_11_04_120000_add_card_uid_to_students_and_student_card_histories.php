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

    public function down(): void
    {
        Schema::dropIfExists('student_card_histories');

        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'card_uid']);
            $table->dropColumn('card_uid');
        });
    }
};
