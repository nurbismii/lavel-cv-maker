<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFamilyFieldsToCvProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('cv_profiles', 'marriage_date')) {
            Schema::table('cv_profiles', function (Blueprint $table) {
                $table->date('marriage_date')->nullable()->after('marital_status');
            });
        }

        if (!Schema::hasColumn('cv_profiles', 'spouse_name')) {
            Schema::table('cv_profiles', function (Blueprint $table) {
                $table->string('spouse_name')->nullable()->after('marriage_date');
            });
        }

        if (!Schema::hasColumn('cv_profiles', 'has_children')) {
            Schema::table('cv_profiles', function (Blueprint $table) {
                $table->boolean('has_children')->default(false)->after('spouse_name');
            });
        }

        if (!Schema::hasColumn('cv_profiles', 'children_names')) {
            Schema::table('cv_profiles', function (Blueprint $table) {
                $table->text('children_names')->nullable()->after('has_children');
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
            Schema::hasColumn('cv_profiles', 'children_names') ? 'children_names' : null,
            Schema::hasColumn('cv_profiles', 'has_children') ? 'has_children' : null,
            Schema::hasColumn('cv_profiles', 'spouse_name') ? 'spouse_name' : null,
            Schema::hasColumn('cv_profiles', 'marriage_date') ? 'marriage_date' : null,
        ]));

        if (count($columns)) {
            Schema::table('cv_profiles', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
}
