<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('parent_1_mobile', 32)->nullable()->after('name');
            $table->string('parent_2_mobile', 32)->nullable()->after('parent_1_mobile');
            $table->date('start_date')->nullable()->after('parent_2_mobile');
            $table->boolean('maths')->default(false)->after('start_date');
            $table->boolean('reading')->default(false)->after('maths');
            $table->decimal('tuition_fee', 10, 2)->nullable()->after('reading');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'parent_1_mobile',
                'parent_2_mobile',
                'start_date',
                'maths',
                'reading',
                'tuition_fee',
            ]);
        });
    }
};
