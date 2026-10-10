<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('students') || ! Schema::hasColumn('students', 'student_id')) {
            return;
        }

        $columnType = Schema::getColumnType('students', 'student_id');
        if (in_array($columnType, ['bigint', 'integer'], true)) {
            return;
        }

        $maxUnsignedBigInt = '18446744073709551615';

        foreach (DB::table('students')->select(['id', 'student_id'])->cursor() as $row) {
            $value = trim((string) $row->student_id);

            if ($value === '' || ! preg_match('/^\d+$/', $value) || $value === '0') {
                throw new RuntimeException(
                    'Cannot convert students.student_id to integer: row id '
                    .$row->id.' has invalid student_id "'
                    .$row->student_id.'". Fix or remove these rows first.'
                );
            }

            if (
                strlen($value) > strlen($maxUnsignedBigInt)
                || (strlen($value) === strlen($maxUnsignedBigInt) && strcmp($value, $maxUnsignedBigInt) > 0)
            ) {
                throw new RuntimeException(
                    'Cannot convert students.student_id to integer: row id '
                    .$row->id.' has student_id out of range ("'
                    .$row->student_id.'"). Fix or remove these rows first.'
                );
            }
        }

        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'student_id']);
        });

        DB::statement(
            'ALTER TABLE students MODIFY student_id BIGINT UNSIGNED NOT NULL'
        );

        Schema::table('students', function (Blueprint $table) {
            $table->unique(['company_id', 'student_id']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('students') || ! Schema::hasColumn('students', 'student_id')) {
            return;
        }

        $columnType = Schema::getColumnType('students', 'student_id');
        if ($columnType === 'string') {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'student_id']);
        });

        DB::statement(
            'ALTER TABLE students MODIFY student_id VARCHAR(64) NOT NULL'
        );

        Schema::table('students', function (Blueprint $table) {
            $table->unique(['company_id', 'student_id']);
        });
    }
};
