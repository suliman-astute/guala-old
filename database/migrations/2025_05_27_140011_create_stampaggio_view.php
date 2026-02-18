 <?php
/* 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
   
        DB::statement("
            CREATE VIEW stampaggio_view AS
            SELECT DISTINCT
                gip.mesOrderNo AS mesOrderNo,
                gip.id AS id,
                gip.parentitemNo AS parentitemNo,
                gmp.itemNo AS itemNo,
                gmp.itemDescription AS itemDescription,
                gmp.machineSatmp AS machineSatmp,
                gmp.machinePress AS machinePress,
                gmp.machinePressDesc AS machinePressDesc,
                CONCAT(gmp.machinePress, ' ', gmp.machinePressDesc) AS machinePressFull,
                gmp.relSequence AS relSequence,
                gmp.quantity AS quantity,
                gmp.quantita_prodotta AS quantita_prodotta,
                gmp.quantity - gmp.quantita_prodotta AS quantita_rimanente,
                ofm.messtatus AS messtatus,
                gip.commento AS commento
            FROM
                ((table_gua_mes_prod_orders AS gmp
                JOIN table_gua_items_in_producion AS gip ON gip.mesOrderNo = gmp.mesOrderNo)
                JOIN orderfrommes AS ofm ON ofm.ordernane = gmp.mesOrderNo)
            WHERE
                gmp.machineSatmp IS NOT NULL
                AND gmp.machineSatmp <> ''
                AND gip.mesOrderNo LIKE '%ST%';
        ");
    }

    
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS stampaggio_view");
    }
}; */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Assicurati che il facade DB sia importato

return new class extends Migration
{
    /**
     * Run the migrations.
     * Questa funzione crea la vista 'stampaggio_view'.
     */
    public function up(): void
    {
        // Elimina la vista se esiste già per evitare errori in caso di riesecuzione della migrazione
        DB::statement("DROP VIEW IF EXISTS stampaggio_view");

        DB::statement("
            CREATE VIEW stampaggio_view AS 
            SELECT 
                mpo.*, 
                mc.GUAPosition AS GUAPosition,
                CONCAT(mpo.machinePress, ' ', mpo.machinePressDesc) AS machinePressFull
            FROM table_gua_mes_prod_orders AS mpo
            LEFT JOIN machine_center AS mc 
                ON mpo.machinePress = mc.no
            WHERE mpo.mesOrderNo LIKE '%ST%';
        ");
    }

    /**
     * Reverse the migrations.
     * Questa funzione elimina la vista 'stampaggio_view' se la migrazione viene annullata.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS stampaggio_view");
    }
};
