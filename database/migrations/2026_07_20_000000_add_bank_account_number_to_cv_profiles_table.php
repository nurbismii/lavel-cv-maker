<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBankAccountNumberToCvProfilesTable extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('cv_profiles', 'bank_account_number')) {
            return;
        }

        $afterColumn = Schema::hasColumn('cv_profiles', 'family_card_number')
            ? 'family_card_number'
            : 'birth_date';

        Schema::table('cv_profiles', function (Blueprint $table) use ($afterColumn) {
            $table->string('bank_account_number', 34)->nullable()->after($afterColumn);
        });
    }

    public function down()
    {
        if (!Schema::hasColumn('cv_profiles', 'bank_account_number')) {
            return;
        }

        Schema::table('cv_profiles', function (Blueprint $table) {
            $table->dropColumn('bank_account_number');
        });
    }
}
