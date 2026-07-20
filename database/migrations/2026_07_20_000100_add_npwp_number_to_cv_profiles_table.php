<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNpwpNumberToCvProfilesTable extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('cv_profiles', 'npwp_number')) {
            return;
        }

        $afterColumn = Schema::hasColumn('cv_profiles', 'bank_account_number')
            ? 'bank_account_number'
            : 'family_card_number';

        Schema::table('cv_profiles', function (Blueprint $table) use ($afterColumn) {
            $table->string('npwp_number', 16)->nullable()->after($afterColumn);
        });
    }

    public function down()
    {
        if (!Schema::hasColumn('cv_profiles', 'npwp_number')) {
            return;
        }

        Schema::table('cv_profiles', function (Blueprint $table) {
            $table->dropColumn('npwp_number');
        });
    }
}
