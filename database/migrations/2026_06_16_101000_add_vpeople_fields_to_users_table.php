<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVpeopleFieldsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('vpeople_nik_encrypted')->nullable()->after('password');
            $table->string('vpeople_nik_hash', 64)->nullable()->unique()->after('vpeople_nik_encrypted');
            $table->timestamp('vpeople_last_synced_at')->nullable()->after('vpeople_nik_hash');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_vpeople_nik_hash_unique');
            $table->dropColumn([
                'vpeople_nik_encrypted',
                'vpeople_nik_hash',
                'vpeople_last_synced_at',
            ]);
        });
    }
}
