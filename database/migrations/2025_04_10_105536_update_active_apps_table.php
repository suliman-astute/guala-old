<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateActiveAppsTable extends Migration
{
    public function up(): void
    {
        Schema::table('active_apps', function (Blueprint $table) {
            // Rinomina il campo name in name_it
            $table->renameColumn('name', 'name_it');

            // Aggiunge il campo name_en
            $table->string('name_en')->nullable()->after('name_it');

            // Aggiunge il campo site_id come foreign key
            $table->unsignedBigInteger('site_id')->nullable()->after('id');
            $table->foreign('site_id')->references('id')->on('sites')->onDelete('set null');

            // Aggiunge il campo icon come CHAR
            $table->char('icon', 255)->nullable()->after('site_id');
        });
    }

    public function down(): void
    {
        Schema::table('active_apps', function (Blueprint $table) {
            // Ripristina il nome originale del campo
            $table->renameColumn('name_it', 'name');

            // Rimuove name_en
            $table->dropColumn('name_en');

            // Rimuove la foreign key e il campo site_id
            $table->dropForeign(['site_id']);
            $table->dropColumn('site_id');

            // Rimuove icon
            $table->dropColumn('icon');
        });
    }
}
