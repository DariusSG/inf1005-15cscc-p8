<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class ModulesReviewsSchema
{
    public function up()
    {
        Capsule::schema()->create('modules', function ($table) {
            $table->increments('id');
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('credits')->default(4);
            $table->timestamps();
        });

        Capsule::schema()->create('reviews', function ($table) {
            $table->increments('id');
            $table->string('module_code');
            $table->unsignedInteger('user_id');
            $table->unsignedTinyInteger('rating');
            $table->string('title');
            $table->text('content');
            $table->string('workload')->nullable();
            $table->string('difficulty')->nullable();
            $table->string('usefulness')->nullable();
            $table->unsignedInteger('upvotes')->default(0);
            $table->unsignedInteger('downvotes')->default(0);
            $table->timestamps();

            $table->foreign('module_code')->references('code')->on('modules')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['module_code', 'user_id']); // one review per user per module
        });

        Capsule::schema()->create('review_votes', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('review_id');
            $table->unsignedInteger('user_id');
            $table->enum('type', ['up', 'down']);
            $table->timestamps();

            $table->foreign('review_id')->references('id')->on('reviews')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['review_id', 'user_id']);
        });

        Capsule::schema()->create('review_reports', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('review_id');
            $table->unsignedInteger('user_id');
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->foreign('review_id')->references('id')->on('reviews')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['review_id', 'user_id']);
        });

        Capsule::schema()->create('review_comments', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('review_id');
            $table->unsignedInteger('user_id');
            $table->text('text');
            $table->timestamps();

            $table->foreign('review_id')->references('id')->on('reviews')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Capsule::schema()->create('tutors', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('name');
            $table->string('module_code')->nullable();
            $table->string('contact')->nullable();
            $table->text('bio')->nullable();
            $table->decimal('rate', 8, 2)->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Capsule::schema()->create('study_groups', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('name');
            $table->string('module_code')->nullable();
            $table->text('description')->nullable();
            $table->string('meeting_time')->nullable();
            $table->string('location')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Capsule::schema()->create('help_requests', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('module_code')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('resolved')->default(false);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('help_requests');
        Capsule::schema()->dropIfExists('study_groups');
        Capsule::schema()->dropIfExists('tutors');
        Capsule::schema()->dropIfExists('review_comments');
        Capsule::schema()->dropIfExists('review_reports');
        Capsule::schema()->dropIfExists('review_votes');
        Capsule::schema()->dropIfExists('reviews');
        Capsule::schema()->dropIfExists('modules');
    }
}