<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrganizationFieldsToCvProfiles extends Migration
{
    public function up()
    {
        foreach ([
            'job_title' => fn(Blueprint $table) => $table->string('job_title')->nullable()->after('position'),
            'job_title_id' => fn(Blueprint $table) => $table->unsignedBigInteger('job_title_id')->nullable()->after('job_title'),
            'organization_position_id' => fn(Blueprint $table) => $table->unsignedBigInteger('organization_position_id')->nullable()->after('job_title_id'),
            'job_level_code' => fn(Blueprint $table) => $table->string('job_level_code', 30)->nullable()->after('organization_position_id'),
            'job_level_rank' => fn(Blueprint $table) => $table->unsignedSmallInteger('job_level_rank')->nullable()->after('job_level_code'),
            'organization_updated_at' => fn(Blueprint $table) => $table->timestamp('organization_updated_at')->nullable()->after('job_level_rank'),
        ] as $column => $definition) {
            if (!Schema::hasColumn('cv_profiles', $column)) {
                Schema::table('cv_profiles', $definition);
            }
        }

        Schema::table('cv_profiles', function (Blueprint $table) {
            $table->index('job_title_id', 'cv_profiles_job_title_idx');
            $table->index('organization_position_id', 'cv_profiles_org_position_idx');
        });
    }

    public function down()
    {
        Schema::table('cv_profiles', function (Blueprint $table) {
            $table->dropIndex('cv_profiles_org_position_idx');
            $table->dropIndex('cv_profiles_job_title_idx');
            $table->dropColumn([
                'job_title',
                'job_title_id',
                'organization_position_id',
                'job_level_code',
                'job_level_rank',
                'organization_updated_at',
            ]);
        });
    }
}
