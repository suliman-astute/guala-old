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
        Schema::table('dictionary_table', function (Blueprint $table) {
            $table->string('column_name')->nullable();
            $table->string('table_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dictionary_table', function (Blueprint $table) {
            $table->dropColumn(['column_name', 'table_name']);
        });
    }
};
