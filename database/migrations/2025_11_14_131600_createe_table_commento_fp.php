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
        Schema::create('commento_lavori_guala_fp', function (Blueprint $table) {
            $table->id();
            // relazioni (puoi usare unsignedBigInteger o foreignId)
            $table->integer('id_riga'); 
            $table->string('testo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commento_lavori_guala_fp');
    }
};
