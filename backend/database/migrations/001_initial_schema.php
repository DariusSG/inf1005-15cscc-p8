<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class InitialSchema
{
    public function up()
    {
        // Users table
        Capsule::schema()->create('users', function ($table) {
            $table->increments('id');
            $table->string('email')->unique();
            $table->string('name');
            $table->string('password');
            $table->string('role')->default('user');
            $table->timestamps();
        });

        // Refresh tokens table
        Capsule::schema()->create('refresh_tokens', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('jti')->unique();
            $table->text('token_hash');
            $table->timestamp('expires_at');
            $table->boolean('revoked')->default(false);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Sessions table
        Capsule::schema()->create('sessions', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('jti')->unique();
            $table->string('refresh_jti')->unique();
            $table->string('ip')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('expires_at');
            $table->boolean('revoked')->default(false);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('sessions');
        Capsule::schema()->dropIfExists('refresh_tokens');
        Capsule::schema()->dropIfExists('users');
    }
}