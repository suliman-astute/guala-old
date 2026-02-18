<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gestione_turni', function (Blueprint $table) {
            // Da INT (o altro) a TEXT per supportare liste (CSV/JSON)
            $table->text('id_operatori')->nullable()->change();
            $table->text('id_macchinari_associati')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('gestione_turni', function (Blueprint $table) {
            // Ripristina il tipo precedente (presumo intero). 
            // Se in origine erano diversi, adegua questi tipi!
            $table->unsignedBigInteger('id_operatori')->nullable()->change();
            $table->unsignedBigInteger('id_macchinari_associati')->nullable()->change();
        });
    }
};
