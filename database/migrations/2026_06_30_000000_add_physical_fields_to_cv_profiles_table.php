<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPhysicalFieldsToCvProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cv_profiles', function (Blueprint $table) {
            $table->unsignedSmallInteger('height_cm')->nullable()->after('gender');
            $table->decimal('weight_kg', 5, 2)->nullable()->after('height_cm');
            $table->string('blood_type', 3)->nullable()->after('weight_kg');
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
                'height_cm',
                'weight_kg',
                'blood_type',
            ]);
        });
    }
}

