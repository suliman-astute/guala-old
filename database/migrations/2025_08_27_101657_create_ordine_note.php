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
        Schema::create('ordine_note', function (Blueprint $t) {
            $t->id();
            $t->string('ordine', 100)->index();
            $t->string('lotto', 100)->index();
            $t->text('nota')->nullable();
            $t->timestamps();
            $t->unique(['ordine', 'lotto']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ordine_note');
    }
};
