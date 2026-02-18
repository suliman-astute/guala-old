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
        Schema::create('gestione_turni_presse', function (Blueprint $table) {
            $table->id();
            // relazioni (puoi usare unsignedBigInteger o foreignId)
            $table->unsignedBigInteger('id_capoturno');
            $table->unsignedBigInteger('id_turno');
            $table->unsignedBigInteger('id_operatori');
            $table->unsignedBigInteger('id_macchinari_associati');
            // campo data
            $table->date('data_turno');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gestione_turni_presse');
    }
};
