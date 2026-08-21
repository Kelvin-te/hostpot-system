<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $connection = Schema::connection('wingufi_core');

        $connection->table('radius_nas', function (Blueprint $table) use ($connection) {
            if (! $connection->hasColumn('radius_nas', 'external_id')) {
                $table->string('external_id', 100)->nullable()->after('management_ip');
            }
            if (! $connection->hasColumn('radius_nas', 'source_system')) {
                $table->string('source_system', 100)->nullable()->after('external_id');
            }
        });

        if (! $this->indexExists('radius_nas', 'radius_nas_tenant_ext_src_unique')) {
            $connection->table('radius_nas', function (Blueprint $table) {
                $table->unique(['tenant_id', 'external_id', 'source_system'], 'radius_nas_tenant_ext_src_unique');
            });
        }

        if ($this->indexExists('network_clients', 'network_clients_external_id_source_system_index')) {
            $connection->table('network_clients', function (Blueprint $table) {
                $table->dropIndex('network_clients_external_id_source_system_index');
            });
        }

        if (! $this->indexExists('network_clients', 'network_clients_tenant_ext_src_unique')) {
            $connection->table('network_clients', function (Blueprint $table) {
                $table->unique(['tenant_id', 'external_id', 'source_system'], 'network_clients_tenant_ext_src_unique');
            });
        }

        if (! $this->indexExists('network_packages', 'network_packages_tenant_ext_src_unique')) {
            $connection->table('network_packages', function (Blueprint $table) {
                $table->unique(['tenant_id', 'external_id', 'source_system'], 'network_packages_tenant_ext_src_unique');
            });
        }

        if ($this->indexExists('network_authorizations', 'network_authorizations_external_id_source_system_unique')) {
            $connection->table('network_authorizations', function (Blueprint $table) {
                $table->dropUnique('network_authorizations_external_id_source_system_unique');
            });
        }

        if (! $this->indexExists('network_authorizations', 'network_auth_tenant_ext_src_unique')) {
            $connection->table('network_authorizations', function (Blueprint $table) {
                $table->unique(['tenant_id', 'external_id', 'source_system'], 'network_auth_tenant_ext_src_unique');
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::connection('wingufi_core')->getConnection();

        $result = $connection->select(
            "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
            [$indexName]
        );

        return count($result) > 0;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('wingufi_core')->table('radius_nas', function (Blueprint $table) {
            $table->dropUnique('radius_nas_tenant_ext_src_unique');
            $table->dropColumn(['external_id', 'source_system']);
        });

        Schema::connection('wingufi_core')->table('network_clients', function (Blueprint $table) {
            $table->dropUnique('network_clients_tenant_ext_src_unique');
            $table->index(['external_id', 'source_system']);
        });

        Schema::connection('wingufi_core')->table('network_packages', function (Blueprint $table) {
            $table->dropUnique('network_packages_tenant_ext_src_unique');
        });

        Schema::connection('wingufi_core')->table('network_authorizations', function (Blueprint $table) {
            $table->dropUnique('network_auth_tenant_ext_src_unique');
            $table->unique(['external_id', 'source_system'], 'network_authorizations_external_id_source_system_unique');
        });
    }
};
