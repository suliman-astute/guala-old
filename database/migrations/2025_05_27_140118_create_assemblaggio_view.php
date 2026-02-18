<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Questa funzione crea la vista 'assemblaggio_view'.
     */
    public function up(): void
    {
        // La tua definizione SQL per la vista 'assemblaggio_view'
        // Rimuovi 'ALGORITHM=UNDEFINED DEFINER=`guala_usr`@`%` SQL SECURITY DEFINER'
        // per le stesse ragioni spiegate in precedenza.
        DB::statement("
            CREATE OR REPLACE VIEW assemblaggio_view AS
            SELECT 
                o.*, 
                CONCAT(o.machineSatmp, ' - ', m.name) AS nome_completo_macchina
            FROM 
                table_gua_mes_prod_orders o
            JOIN 
                machine_center m 
                ON o.machineSatmp = m.GUAPosition
            WHERE 
                o.mesOrderNo LIKE '%AS%';
        ");
    }

    /**
     * Reverse the migrations.
     * Questa funzione elimina la vista 'assemblaggio_view' se la migrazione viene annullata.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS assemblaggio_view");
    }
};
