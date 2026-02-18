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
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_id')->nullable()->default(false);;
            $table->string('cognome')->nullable()->default(false);;
            $table->string('matricola')->nullable()->default(false);;
            $table->integer('valido');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['user_id', 'cognome', 'matricola', 'valido']);
        });
    }
};
