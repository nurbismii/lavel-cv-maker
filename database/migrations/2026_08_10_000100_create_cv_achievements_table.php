<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCvAchievementsTable extends Migration
{
    public function up()
    {
        Schema::create('cv_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cv_profile_id')->constrained()->cascadeOnDelete();
            $table->string('field', 32);
            $table->string('other_field')->nullable();
            $table->string('achievement_type');
            $table->string('rank', 100);
            $table->string('level', 32);
            $table->string('other_level')->nullable();
            $table->string('period', 7);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['cv_profile_id', 'period']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('cv_achievements');
    }
}
