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
        Schema::create('bisio_progetti_stain', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('DescrMacchinaEstesa');
            $table->string('StatoOperazione');
            $table->string('nrordinesap');
            $table->string('codarticolo');
            $table->string('DescrizioneArticolo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bisio_progetti_stain', function (Blueprint $table) {
            $table->dropColumn(['nome', 'DescrMacchinaEstesa', 'StatoOperazione', 'nrordinesap', 'codarticolo', 'DescrizioneArticolo']);
        });
    }
};
