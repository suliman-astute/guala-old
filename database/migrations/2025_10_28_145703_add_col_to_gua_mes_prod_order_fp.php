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
        Schema::table('gua_mes_prod_orders_fp', function (Blueprint $table) {
            $table->dateTime('startingdatetime')->nullable()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gua_mes_prod_orders_fp', function (Blueprint $table) {
            $table->dropColumn(['startingdatetime']);
        });
    }
};
