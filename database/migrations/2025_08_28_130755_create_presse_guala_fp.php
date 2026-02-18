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
        Schema::create('presse_guala_fp', function (Blueprint $table) {
            $table->id();
            $table->string("GUAPosition")->nullable();
            $table->string("id_mes");
            $table->string("id_piovan")->nullable();
            $table->integer('ingressi_usati'); 
            $table->string("GUAMachineCenterType")->nullable();
            $table->integer('azienda'); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presse_guala_fp');
    }
};
