<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class RelaxCvDraftRepeatableFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE cv_experiences MODIFY position VARCHAR(255) NULL');
        DB::statement('ALTER TABLE cv_experiences MODIFY company VARCHAR(255) NULL DEFAULT "PT VDNI"');
        DB::statement('ALTER TABLE cv_experiences MODIFY start_month DATE NULL');

        DB::statement('ALTER TABLE cv_educations MODIFY level VARCHAR(16) NULL');
        DB::statement('ALTER TABLE cv_educations MODIFY institution VARCHAR(255) NULL');

        DB::statement('ALTER TABLE cv_certifications MODIFY name VARCHAR(255) NULL');
        DB::statement('ALTER TABLE cv_languages MODIFY language VARCHAR(255) NULL');
        DB::statement('ALTER TABLE cv_projects MODIFY name VARCHAR(255) NULL');
        DB::statement('ALTER TABLE cv_organizations MODIFY organization_name VARCHAR(255) NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE cv_experiences MODIFY position VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE cv_experiences MODIFY company VARCHAR(255) NOT NULL DEFAULT "PT VDNI"');
        DB::statement('ALTER TABLE cv_experiences MODIFY start_month DATE NOT NULL');

        DB::statement('ALTER TABLE cv_educations MODIFY level VARCHAR(16) NOT NULL');
        DB::statement('ALTER TABLE cv_educations MODIFY institution VARCHAR(255) NOT NULL');

        DB::statement('ALTER TABLE cv_certifications MODIFY name VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE cv_languages MODIFY language VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE cv_projects MODIFY name VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE cv_organizations MODIFY organization_name VARCHAR(255) NOT NULL');
    }
}
