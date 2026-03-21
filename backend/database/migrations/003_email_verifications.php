<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class EmailVerifications
{
    public function up()
    {
        Capsule::schema()->create('email_verifications', function ($table) {
            $table->increments('id');
            $table->string('email')->index();
            $table->string('token', 64)->unique();   // SHA-256 hex = 64 chars
            $table->timestamp('expires_at');
            $table->boolean('used')->default(false);
            $table->timestamps();
        });

        // Add name column to users (was missing from initial schema)
        Capsule::schema()->table('users', function ($table) {
            $table->string('name')->nullable()->after('email');
        });
    }

    public function down()
    {
        Capsule::schema()->table('users', function ($table) {
            $table->dropColumn('name');
        });
        Capsule::schema()->dropIfExists('email_verifications');
    }
}