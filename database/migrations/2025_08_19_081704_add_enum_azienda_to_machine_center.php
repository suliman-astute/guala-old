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
        Schema::table('machine_center', function (Blueprint $table) {
            $table->string("id_piovan")->nullable();
            $table->enum('azienda', ['','Guala Dispensing', 'Bisio', 'Messico', 'Romania'])->default('');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('machine_center', function (Blueprint $table) {
            $table->dropColumn(['id_piovan', 'azienda']);
        });
    }
};
