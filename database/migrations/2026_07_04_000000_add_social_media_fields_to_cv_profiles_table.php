<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSocialMediaFieldsToCvProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('cv_profiles', 'instagram')) {
            Schema::table('cv_profiles', function (Blueprint $table) {
                $table->string('instagram')->nullable()->after('email');
            });
        }

        if (!Schema::hasColumn('cv_profiles', 'linkedin')) {
            Schema::table('cv_profiles', function (Blueprint $table) {
                $table->string('linkedin')->nullable()->after('instagram');
            });
        }

        if (!Schema::hasColumn('cv_profiles', 'facebook')) {
            Schema::table('cv_profiles', function (Blueprint $table) {
                $table->string('facebook')->nullable()->after('linkedin');
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
        $columns = array_filter([
            Schema::hasColumn('cv_profiles', 'facebook') ? 'facebook' : null,
            Schema::hasColumn('cv_profiles', 'linkedin') ? 'linkedin' : null,
            Schema::hasColumn('cv_profiles', 'instagram') ? 'instagram' : null,
        ]);

        if (!count($columns)) {
            return;
        }

        Schema::table('cv_profiles', function (Blueprint $table) use ($columns) {
            $table->dropColumn($columns);
        });
    }
}
