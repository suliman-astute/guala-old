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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('destinazione_utenti', ['','Guala Dispensing', 'Bisio', 'Messico', 'Romania'])->default('');
            $table->enum('ruolo_personale', ['','Operatore Assemblaggio','Capo turno Assemblaggio','Operatore Stampaggio','Capo turno Stampaggio'])->default('');
            $table->enum('stato', ['','attivo', 'inattivo', 'sospeso'])->default('');     
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['destinazione_utenti', 'ruolo_personale']);
        });
    }
};
