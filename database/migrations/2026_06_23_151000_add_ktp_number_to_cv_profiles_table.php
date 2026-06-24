<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddKtpNumberToCvProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('cv_profiles', 'ktp_number')) {
            return;
        }

        Schema::table('cv_profiles', function (Blueprint $table) {
            $table->string('ktp_number', 16)->nullable()->after('birth_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasColumn('cv_profiles', 'ktp_number')) {
            return;
        }

        Schema::table('cv_profiles', function (Blueprint $table) {
            $table->dropColumn('ktp_number');
        });
    }
}
