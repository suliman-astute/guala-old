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
        //Ho creato una tabella dummy per test manterrei la struttura di quella di origine.
        Schema::create('ext_infos', function (Blueprint $table) {
            $table->id();
            $table->char("val1")->nullable();
            $table->char("val2")->nullable();
            $table->char("val3")->nullable();
            /* Qui ho messo l'index supponendo sia il set di campi univoci*/
            $table->char("stampe")->nullable()->index();
            $table->char("seq")->nullable()->index();
            $table->char("code")->nullable()->index();
            $table->char("n_order")->nullable();
            $table->char("n_order_erp")->nullable();
            $table->integer("qta_ric")->nullable();
            $table->integer("qta_prod")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ext_infos');
    }
};
