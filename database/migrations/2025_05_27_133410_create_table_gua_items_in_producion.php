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
        Schema::create('table_gua_items_in_producion', function (Blueprint $table) {
            $table->id();
            $table->integer("entryNo");
            $table->string("componentNo");
            $table->string("parentitemNo");
            $table->text("compDescription");
            $table->integer("levelCode");
            $table->integer("qty");
            $table->string("unitOfMeasure");
            $table->string("prodorderno");
            $table->string("mesOrderNo");
            $table->text("commento");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_gua_items_in_producion');
    }
};
