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
         Schema::create('note_macchine_operatori', function (Blueprint $table) {
            $table->id();

            // FK verso machine_center(id) e users(id)
            $table->foreignId('id_macchina')
                  ->constrained('machine_center')   // tabella esistente: machine_center
                  ->cascadeOnDelete();

            $table->foreignId('id_operatore')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->date('data');                   // giorno a cui si riferisce la nota
            $table->text('nota')->nullable();       // testo nota

            $table->timestamps();

            // una sola nota per macchina-operatore-data
            $table->unique(['id_macchina', 'id_operatore', 'data'], 'uq_macc_op_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('note_macchine_operatori');
    }
};
