<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInterestsToCvProfilesTable extends Migration
{
    public function up()
    {
        Schema::table('cv_profiles', function (Blueprint $table) {
            $table->json('hobbies')->nullable()->after('non_technical_skills');
            $table->string('other_hobby')->nullable()->after('hobbies');
            $table->json('talents')->nullable()->after('other_hobby');
            $table->string('other_talent')->nullable()->after('talents');
        });
    }

    public function down()
    {
        Schema::table('cv_profiles', function (Blueprint $table) {
            $table->dropColumn(['hobbies', 'other_hobby', 'talents', 'other_talent']);
        });
    }
}
