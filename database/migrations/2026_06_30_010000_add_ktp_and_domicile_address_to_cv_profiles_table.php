<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddKtpAndDomicileAddressToCvProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cv_profiles', function (Blueprint $table) {
            $table->text('ktp_address')->nullable()->after('mother_name');
            $table->boolean('domicile_same_as_ktp')->default(false)->after('ktp_address');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cv_profiles', function (Blueprint $table) {
            $table->dropColumn(['ktp_address', 'domicile_same_as_ktp']);
        });
    }
}
