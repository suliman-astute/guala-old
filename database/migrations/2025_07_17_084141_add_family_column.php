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
        Schema::table('table_gua_mes_prod_orders', function (Blueprint $table) {
            $table->string("family")->nullable();;
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('table_gua_mes_prod_orders', function (Blueprint $table) {
            $table->dropColumn(['family']);
        });
    }
};
