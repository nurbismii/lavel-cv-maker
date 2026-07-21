<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AllowMultipleFilesForSelectedCvDocuments extends Migration
{
    public function up()
    {
        Schema::table('cv_documents', function (Blueprint $table) {
            $table->index(['cv_profile_id', 'type']);
            $table->dropUnique(['cv_profile_id', 'type']);
        });
    }

    public function down()
    {
        Schema::table('cv_documents', function (Blueprint $table) {
            $table->dropIndex(['cv_profile_id', 'type']);
            $table->unique(['cv_profile_id', 'type']);
        });
    }
}
