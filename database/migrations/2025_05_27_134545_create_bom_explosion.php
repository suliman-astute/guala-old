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
        Schema::create('bom_explosion', function (Blueprint $table) {
            $table->id();
            $table->integer("xLevel");
            $table->string("productionBOMNo");
            $table->string("BOMReplSystem");
            $table->string("BOMInvPostGr");
            $table->string("No");
            $table->string("ReplSystem");
            $table->string("InvPostGr");
            $table->string("UoM");
            $table->double("QtyPer");
            $table->double("PercScarti");
            $table->string("PathString");
            $table->integer("PathLength");
            $table->date("StartingDate")->nullable();
            $table->string("Company");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bom_explosion');
    }
};
