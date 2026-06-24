<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCvEmergencyContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cv_emergency_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cv_profile_id')->constrained()->cascadeOnDelete();
            $table->string('phone', 32)->nullable();
            $table->string('name')->nullable();
            $table->string('relationship', 64)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['cv_profile_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cv_emergency_contacts');
    }
}
