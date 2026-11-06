<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_email_templates', function (Blueprint $table) {
            $table->json('to_recipients')->nullable()->after('bcc_recipients');
        });
    }

    public function down(): void
    {
        Schema::table('admin_email_templates', function (Blueprint $table) {
            $table->dropColumn('to_recipients');
        });
    }
};
