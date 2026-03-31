<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class Migration_002_password_resets
{
    public function up(): void
    {
        if (Capsule::schema()->hasTable('password_resets')) {
            return;
        }

        Capsule::schema()->create('password_resets', function ($table) {
            $table->increments('id');
            $table->string('email')->index();
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->boolean('used')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('password_resets');
    }
}