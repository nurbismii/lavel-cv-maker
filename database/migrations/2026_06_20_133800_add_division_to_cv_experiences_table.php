<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDivisionToCvExperiencesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('cv_experiences', 'division')) {
            return;
        }

        Schema::table('cv_experiences', function (Blueprint $table) {
            $table->string('division')->nullable()->after('department');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasColumn('cv_experiences', 'division')) {
            return;
        }

        Schema::table('cv_experiences', function (Blueprint $table) {
            $table->dropColumn('division');
        });
    }
}
