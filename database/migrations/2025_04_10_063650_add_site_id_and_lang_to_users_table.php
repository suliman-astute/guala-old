<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSiteIdAndLangToUsersTable extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Aggiunge il campo site_id come unsignedBigInteger
            $table->unsignedBigInteger('site_id')->nullable()->after('id');

            // Aggiunge la foreign key
            $table->foreign('site_id')->references('id')->on('sites')->onDelete('set null');

            // Aggiunge il campo lang
            $table->string('lang', 2)->nullable()->after('site_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Rimuove la foreign key e il campo site_id
            $table->dropForeign(['site_id']);
            $table->dropColumn('site_id');

            // Rimuove il campo lang
            $table->dropColumn('lang');
        });
    }
}
