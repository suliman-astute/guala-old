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
        Schema::table('qta_guala_pro_rom', function (Blueprint $table) {
            $table->string("UM")->nullable();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qta_guala_pro_rom', function (Blueprint $table) {
            $table->dropColumn(['UM']);
        });
    }
};
