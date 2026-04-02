<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class Migration_004_study_group_members
{
    public function up(): void
    {
        if (Capsule::schema()->hasTable('study_group_members')) {
            return;
        }

        Capsule::schema()->create('study_group_members', function ($table) {
            $table->unsignedInteger('study_group_id');
            $table->unsignedInteger('user_id');
            $table->timestamps();

            $table->primary(['study_group_id', 'user_id']);
            $table->foreign('study_group_id')->references('id')->on('study_groups')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('study_group_members');
    }
}
