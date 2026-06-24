<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCurrentJobEntryDateToCvProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('cv_profiles', 'current_job_entry_date')) {
            return;
        }

        Schema::table('cv_profiles', function (Blueprint $table) {
            $table->date('current_job_entry_date')->nullable()->after('position');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasColumn('cv_profiles', 'current_job_entry_date')) {
            return;
        }

        Schema::table('cv_profiles', function (Blueprint $table) {
            $table->dropColumn('current_job_entry_date');
        });
    }
}
