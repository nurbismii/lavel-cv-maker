<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRtRwToCvProfilesTable extends Migration
{
    public function up()
    {
        Schema::table('cv_profiles', function (Blueprint $table) {
            $table->string('rt', 3)->nullable()->after('ktp_address');
            $table->string('rw', 3)->nullable()->after('rt');
        });
    }

    public function down()
    {
        Schema::table('cv_profiles', function (Blueprint $table) {
            $table->dropColumn(['rt', 'rw']);
        });
    }
}
