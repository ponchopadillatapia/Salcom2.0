<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('proveedores_users', 'codigo_compras')) {
            return;
        }

        if (Schema::hasColumn('proveedores_users', 'id_proveedor')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('ALTER TABLE proveedores_users RENAME COLUMN codigo_compras TO id_proveedor');
        } else {
            Schema::table('proveedores_users', function (Blueprint $table) {
                $table->dropIndex(['codigo_compras']);
            });

            Schema::table('proveedores_users', function (Blueprint $table) {
                $table->renameColumn('codigo_compras', 'id_proveedor');
            });
        }

        if (! $this->indexExists('proveedores_users', 'proveedores_users_id_proveedor_index')) {
            Schema::table('proveedores_users', function (Blueprint $table) {
                $table->index('id_proveedor');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('proveedores_users', 'id_proveedor')) {
            return;
        }

        if (Schema::hasColumn('proveedores_users', 'codigo_compras')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('ALTER TABLE proveedores_users RENAME COLUMN id_proveedor TO codigo_compras');
        } else {
            Schema::table('proveedores_users', function (Blueprint $table) {
                $table->dropIndex(['id_proveedor']);
            });

            Schema::table('proveedores_users', function (Blueprint $table) {
                $table->renameColumn('id_proveedor', 'codigo_compras');
            });

            Schema::table('proveedores_users', function (Blueprint $table) {
                $table->index('codigo_compras');
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");

            return collect($indexes)->contains(fn ($row) => ($row->name ?? null) === $index);
        }

        $database = Schema::getConnection()->getDatabaseName();
        $result = DB::select(
            'SELECT COUNT(*) AS total FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $index]
        );

        return ($result[0]->total ?? 0) > 0;
    }
};
