<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVpeopleFamilyIdentityFieldsToCvProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('cv_profiles', 'family_card_number')) {
            $afterColumn = Schema::hasColumn('cv_profiles', 'ktp_number') ? 'ktp_number' : 'birth_date';

            Schema::table('cv_profiles', function (Blueprint $table) use ($afterColumn) {
                $table->string('family_card_number', 20)->nullable()->after($afterColumn);
            });
        }

        if (!Schema::hasColumn('cv_profiles', 'religion')) {
            Schema::table('cv_profiles', function (Blueprint $table) {
                $table->string('religion')->nullable()->after('gender');
            });
        }

        if (!Schema::hasColumn('cv_profiles', 'mother_name')) {
            $afterColumn = Schema::hasColumn('cv_profiles', 'spouse_name') ? 'spouse_name' : 'marital_status';

            Schema::table('cv_profiles', function (Blueprint $table) use ($afterColumn) {
                $table->string('mother_name')->nullable()->after($afterColumn);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $columns = array_values(array_filter([
            Schema::hasColumn('cv_profiles', 'mother_name') ? 'mother_name' : null,
            Schema::hasColumn('cv_profiles', 'religion') ? 'religion' : null,
            Schema::hasColumn('cv_profiles', 'family_card_number') ? 'family_card_number' : null,
        ]));

        if (count($columns)) {
            Schema::table('cv_profiles', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
}
