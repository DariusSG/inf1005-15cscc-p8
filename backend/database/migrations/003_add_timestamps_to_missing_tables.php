<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class Migration_003_add_timestamps_to_missing_tables
{
    public function up()
    {
        // module_semesters
        Capsule::schema()->table('module_semesters', function ($table) {
            $table->timestamps();
        });

        // module_prereqs
        Capsule::schema()->table('module_prereqs', function ($table) {
            $table->timestamps();
        });

        // tutor_modules
        Capsule::schema()->table('tutor_modules', function ($table) {
            $table->timestamps();
        });
    }

    public function down()
    {
        Capsule::schema()->table('module_semesters', function ($table) {
            $table->dropColumn(['created_at', 'updated_at']);
        });

        Capsule::schema()->table('module_prereqs', function ($table) {
            $table->dropColumn(['created_at', 'updated_at']);
        });

        Capsule::schema()->table('tutor_modules', function ($table) {
            $table->dropColumn(['created_at', 'updated_at']);
        });
    }
}