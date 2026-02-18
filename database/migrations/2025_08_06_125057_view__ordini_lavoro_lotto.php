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
        Schema::create('ordini_lavoro_lotti', function (Blueprint $table) {
            $table->id();
            $table->string('Ordine');
            $table->string('Lotto');
            $table->string('ArticoloCodice');
            $table->string('ArticoloDescrizione');
            $table->string('ClienteCodice');
            $table->string('ClienteDescrizione');
            $table->string('QtaPrevOrdin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ordini_lavoro_lotti', function (Blueprint $table) {
            $table->dropColumn(['Ordine', 'Lotto', 'ArticoloCodice', 'ArticoloDescrizione', 'ClienteCodice', 'ClienteDescrizione', 'OrQtaPrevOrdindine']);
        });
    }
};
