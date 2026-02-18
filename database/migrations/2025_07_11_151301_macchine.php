<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('macchine', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('macchine', function (Blueprint $table) {
            $table->dropColumn(['nome']);
        });
    }
};
