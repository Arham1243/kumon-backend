<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /** @var list<string> */
    private array $names = [
        'reports.scheduled-jobs.view',
        'reports.scheduled-jobs.edit',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('admin_permissions')) {
            return;
        }

        $ids = DB::table('admin_permissions')
            ->where('guard_name', 'sanctum')
            ->whereIn('name', $this->names)
            ->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        if (Schema::hasTable('admin_role_has_permissions')) {
            DB::table('admin_role_has_permissions')->whereIn('permission_id', $ids)->delete();
        }

        if (Schema::hasTable('admin_model_has_permissions')) {
            DB::table('admin_model_has_permissions')->whereIn('permission_id', $ids)->delete();
        }

        DB::table('admin_permissions')->whereIn('id', $ids)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (! Schema::hasTable('admin_permissions')) {
            return;
        }

        $now = now();

        foreach ($this->names as $name) {
            DB::table('admin_permissions')->updateOrInsert(
                ['name' => $name, 'guard_name' => 'sanctum'],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
