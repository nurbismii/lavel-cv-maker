<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLocationFieldsToCvProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cv_profiles', function (Blueprint $table) {
            $table->string('province_id', 32)->nullable()->after('marital_status');
            $table->string('province_name')->nullable()->after('province_id');
            $table->string('regency_id', 32)->nullable()->after('province_name');
            $table->string('regency_name')->nullable()->after('regency_id');
            $table->string('district_id', 32)->nullable()->after('regency_name');
            $table->string('district_name')->nullable()->after('district_id');
            $table->string('village_id', 32)->nullable()->after('district_name');
            $table->string('village_name')->nullable()->after('village_id');
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
            $table->dropColumn([
                'province_id',
                'province_name',
                'regency_id',
                'regency_name',
                'district_id',
                'district_name',
                'village_id',
                'village_name',
            ]);
        });
    }
}
