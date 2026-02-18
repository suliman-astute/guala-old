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
        Schema::table('tabella_appoggio_macchine', function (Blueprint $table) {
            $table->integer("ingressi_usati")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tabella_appoggio_macchine', function (Blueprint $table) {
            $table->dropColumn(['ingressi_usati']);
        });
    }
};
