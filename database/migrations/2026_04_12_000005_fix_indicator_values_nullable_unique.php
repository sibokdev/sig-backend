<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

/**
 * MySQL treats NULL != NULL in UNIQUE indexes, so two rows with
 * (cve=X, year=Y, cve_mun=NULL) would both INSERT instead of upsert.
 *
 * Fix: change cve_mun, cve_seccion, period to NOT NULL DEFAULT '' so the
 * UNIQUE constraint works correctly with ON DUPLICATE KEY UPDATE.
 */
class FixIndicatorValuesNullableUnique extends Migration
{
    public function up()
    {
        // Null → empty string for existing rows before altering columns
        DB::statement("UPDATE indicator_values SET cve_mun    = '' WHERE cve_mun    IS NULL");
        DB::statement("UPDATE indicator_values SET cve_seccion= '' WHERE cve_seccion IS NULL");
        DB::statement("UPDATE indicator_values SET period     = '' WHERE period      IS NULL");

        Schema::table('indicator_values', function (Blueprint $table) {
            // Drop the existing unique index first
            $table->dropUnique('indicator_values_unique');
        });

        // Change columns to NOT NULL DEFAULT ''
        DB::statement("ALTER TABLE indicator_values MODIFY cve_mun    CHAR(3)    NOT NULL DEFAULT ''");
        DB::statement("ALTER TABLE indicator_values MODIFY cve_seccion CHAR(4)   NOT NULL DEFAULT ''");
        DB::statement("ALTER TABLE indicator_values MODIFY period      VARCHAR(20) NOT NULL DEFAULT ''");

        Schema::table('indicator_values', function (Blueprint $table) {
            // Re-add unique index (now '' acts as sentinel for "not applicable")
            $table->unique(
                ['indicador_clave', 'year', 'period', 'cve_ent', 'cve_mun', 'cve_seccion'],
                'indicator_values_unique'
            );
        });
    }

    public function down()
    {
        Schema::table('indicator_values', function (Blueprint $table) {
            $table->dropUnique('indicator_values_unique');
        });

        DB::statement("ALTER TABLE indicator_values MODIFY cve_mun    CHAR(3)    NULL");
        DB::statement("ALTER TABLE indicator_values MODIFY cve_seccion CHAR(4)   NULL");
        DB::statement("ALTER TABLE indicator_values MODIFY period      VARCHAR(20) NULL");

        // Restore NULL semantics (won't restore actual NULLs from empty strings)
        Schema::table('indicator_values', function (Blueprint $table) {
            $table->unique(
                ['indicador_clave', 'year', 'period', 'cve_ent', 'cve_mun', 'cve_seccion'],
                'indicator_values_unique'
            );
        });
    }
}
