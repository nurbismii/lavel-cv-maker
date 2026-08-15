<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEmailVerificationExpiresAtToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('email_verification_expires_at')
                ->nullable()
                ->after('email_verified_at')
                ->index();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['email_verification_expires_at']);
            $table->dropColumn('email_verification_expires_at');
        });
    }
}
