<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterRehDispositivosAcessoAddPollingFields extends Migration
{
    public function up(): void
    {
        Schema::table('reh_dispositivos_acesso', function (Blueprint $table) {
            $table->unsignedBigInteger('dis_ultimo_access_log_id')->nullable()->after('dis_status');
            $table->dateTime('dis_ultima_coleta_em')->nullable()->after('dis_ultimo_access_log_id');
        });
    }

    public function down(): void
    {
        Schema::table('reh_dispositivos_acesso', function (Blueprint $table) {
            $table->dropColumn(['dis_ultimo_access_log_id', 'dis_ultima_coleta_em']);
        });
    }
}
