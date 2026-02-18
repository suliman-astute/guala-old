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
        Schema::create('turni', function (Blueprint $table) {
            $table->id();
            $table->string('nome_turno');
            $table->integer('inizio');
            $table->integer('fine');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('turni', function (Blueprint $table) {
            $table->dropColumn(['nome_turno', 'inizio', 'fine']);
        });
    }
};
