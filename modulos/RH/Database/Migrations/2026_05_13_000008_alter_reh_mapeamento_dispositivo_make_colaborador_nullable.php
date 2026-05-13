<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AlterRehMapeamentoDispositivoMakeColaboradorNullable extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE reh_mapeamento_dispositivo MODIFY map_col_id INT UNSIGNED NULL');
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE reh_mapeamento_dispositivo ALTER COLUMN map_col_id DROP NOT NULL');
        }
    }

    public function down(): void
    {
        if (DB::table('reh_mapeamento_dispositivo')->whereNull('map_col_id')->exists()) {
            throw new \RuntimeException('Existem mapeamentos sem colaborador vinculado. Ajuste os registros antes de reverter esta migration.');
        }

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE reh_mapeamento_dispositivo MODIFY map_col_id INT UNSIGNED NOT NULL');
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE reh_mapeamento_dispositivo ALTER COLUMN map_col_id SET NOT NULL');
        }
    }
}
