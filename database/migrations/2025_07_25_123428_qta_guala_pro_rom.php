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
        Schema::create('qta_guala_pro_rom', function (Blueprint $table) {
            $table->id();
            $table->string('codice_udc');
            $table->string('sku');
            $table->double('Quantita');
            $table->string('Stato_udc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qta_guala_pro_rom', function (Blueprint $table) {
            $table->qta_guala_pro_rom(['codice_udc', 'sku', 'Quantita', 'Stato_udc']);
        });
    }
};
