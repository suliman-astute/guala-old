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
        Schema::create('gua_mes_prod_orders_fp', function (Blueprint $table) {
            $table->id();
            $table->string("mesOrderNo");
            $table->string("mesStatus");
            $table->string("itemNo");
            $table->text("itemDescription");
            $table->string("machineSatmp");
            $table->string("machinePress");
            $table->string("machinePressDesc");
            $table->string("guaCustomerNO");
            $table->string("guaCustomName");
            $table->string("guaCustomerOrder");
            $table->integer("quantity");
            $table->integer("relSequence");
            $table->integer("quantita_prodotta")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gua_mes_prod_orders_fp');
    }
};
