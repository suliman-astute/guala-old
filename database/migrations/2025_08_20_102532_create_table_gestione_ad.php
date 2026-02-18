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
        Schema::create('table_gestione_ad', function (Blueprint $table) {
            $table->id();
            $table->string('dominio')->nullable();
            $table->string('host')->nullable();
            $table->string('base_dn')->nullable();
            $table->integer('porta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_gestione_ad');
    }
};
