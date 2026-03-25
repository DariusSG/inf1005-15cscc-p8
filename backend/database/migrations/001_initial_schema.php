<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class Migration_001_initial_schema
{
    public function up()
    {
        // ----------------------------------------------------------------
        // users
        // Fields: email, name, password, role (student|admin), verified
        // ----------------------------------------------------------------
        Capsule::schema()->create('users', function ($table) {
            $table->increments('id');
            $table->string('email')->unique();
            $table->string('name');
            $table->string('password');
            $table->enum('role', ['student', 'admin'])->default('student');
            $table->softDeletes();
            $table->timestamps(); // created_at exposed as created_at in API
        });

        // ----------------------------------------------------------------
        // Auth: refresh tokens & sessions (unchanged)
        // ----------------------------------------------------------------
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

        // ----------------------------------------------------------------
        // Email verifications (from migration 003)
        // ----------------------------------------------------------------
        Capsule::schema()->create('email_verifications', function ($table) {
            $table->increments('id');
            $table->string('email')->index();
            $table->string('token', 64)->unique(); // SHA-256 hex
            $table->timestamp('expires_at');
            $table->boolean('used')->default(false);
            $table->timestamps();
        });

        // ----------------------------------------------------------------
        // modules
        // Added: faculty (enum), prereqs, semesters
        // ----------------------------------------------------------------
        Capsule::schema()->create('modules', function ($table) {
            $table->increments('id');
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('faculty', ['ICT', 'Business', 'Engineering', 'HSS'])->nullable();
            $table->unsignedTinyInteger('credits')->default(4);
            $table->softDeletes();
            $table->timestamps();
        });

        // prereqs: many-to-many self-join on modules
        Capsule::schema()->create('module_prereqs', function ($table) {
            $table->string('module_code');
            $table->string('prereq_code');
            $table->primary(['module_code', 'prereq_code']);

            $table->foreign('module_code')->references('code')->on('modules')->onDelete('cascade');
            $table->foreign('prereq_code')->references('code')->on('modules')->onDelete('cascade');
        });

        // semesters a module is offered in (e.g. 1, 2, special term)
        Capsule::schema()->create('module_semesters', function ($table) {
            $table->increments('id');
            $table->string('module_code');
            $table->unsignedTinyInteger('semester'); // e.g. 1, 2, 3
            $table->unique(['module_code', 'semester']);

            $table->foreign('module_code')->references('code')->on('modules')->onDelete('cascade');
        });

        // ----------------------------------------------------------------
        // reviews
        // Added: voters{} stored in review_votes (already existed)
        // Removed: denormalised upvotes/downvotes (computed from votes table)
        // voters{} = keyed map of user_id -> vote type, resolved at query time
        // reportedBy[] resolved from review_reports
        // ----------------------------------------------------------------
        Capsule::schema()->create('reviews', function ($table) {
            $table->increments('id');
            $table->string('module_code');
            $table->unsignedInteger('user_id');  // author
            $table->unsignedTinyInteger('rating');
            $table->string('title');
            $table->text('content');
            $table->string('workload')->nullable();
            $table->string('difficulty')->nullable();
            $table->string('usefulness')->nullable();
            $table->softDeletes();
            $table->timestamps(); // created_at exposed as `date`

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

        // ----------------------------------------------------------------
        // review_comments
        // Fields: a (author shortname stored as user_id, resolved at query),
        //         t (text), time (created_at)
        // ----------------------------------------------------------------
        Capsule::schema()->create('review_comments', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('review_id');
            $table->unsignedInteger('user_id'); // author -> shortname resolved at query time
            $table->text('text');               // `t` in API response
            $table->timestamps();               // created_at exposed as `time`

            $table->foreign('review_id')->references('id')->on('reviews')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // ----------------------------------------------------------------
        // tutors
        // Added: modules[] (many-to-many), contactEmail
        // Replaced: single module_code with pivot table tutor_modules
        // ----------------------------------------------------------------
        Capsule::schema()->create('tutors', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('user_id'); // userEmail resolved from user
            $table->string('name');
            $table->string('contact_email')->nullable(); // contactEmail
            $table->text('bio')->nullable();
            $table->decimal('rate', 8, 2)->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // modules[] on tutor: many-to-many
        Capsule::schema()->create('tutor_modules', function ($table) {
            $table->unsignedInteger('tutor_id');
            $table->string('module_code');
            $table->primary(['tutor_id', 'module_code']);

            $table->foreign('tutor_id')->references('id')->on('tutors')->onDelete('cascade');
            $table->foreign('module_code')->references('code')->on('modules')->onDelete('cascade');
        });

        // ----------------------------------------------------------------
        // help_requests
        // Added: urgency (low|medium|high), contact_email, has_bounty,
        //        bounty_amount, status (open|solved), responses[]
        // ----------------------------------------------------------------
        Capsule::schema()->create('help_requests', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');         // userEmail resolved from user
            $table->string('module_code')->nullable();  // module
            $table->string('title');
            $table->text('description')->nullable();    // desc
            $table->enum('urgency', ['low', 'medium', 'high'])->default('low');
            $table->string('contact_email')->nullable(); // contactEmail
            $table->boolean('has_bounty')->default(false);
            $table->decimal('bounty_amount', 8, 2)->nullable();
            $table->enum('status', ['open', 'solved'])->default('open');
            $table->softDeletes();
            $table->timestamps(); // created_at

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('module_code')->references('code')->on('modules')->onDelete('cascade');
        });

        // responses[] on help_request
        Capsule::schema()->create('help_responses', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('help_request_id');
            $table->unsignedInteger('user_id');
            $table->text('content');
            $table->timestamps();

            $table->foreign('help_request_id')->references('id')->on('help_requests')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // ----------------------------------------------------------------
        // study_groups (unchanged, retained from original schema)
        // ----------------------------------------------------------------
        Capsule::schema()->create('study_groups', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('name');
            $table->string('module_code')->nullable();
            $table->text('description')->nullable();
            $table->string('meeting_time')->nullable();
            $table->string('location')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('help_responses');
        Capsule::schema()->dropIfExists('help_requests');
        Capsule::schema()->dropIfExists('tutor_modules');
        Capsule::schema()->dropIfExists('tutors');
        Capsule::schema()->dropIfExists('review_comments');
        Capsule::schema()->dropIfExists('review_reports');
        Capsule::schema()->dropIfExists('review_votes');
        Capsule::schema()->dropIfExists('reviews');
        Capsule::schema()->dropIfExists('module_semesters');
        Capsule::schema()->dropIfExists('module_prereqs');
        Capsule::schema()->dropIfExists('modules');
        Capsule::schema()->dropIfExists('email_verifications');
        Capsule::schema()->dropIfExists('sessions');
        Capsule::schema()->dropIfExists('refresh_tokens');
        Capsule::schema()->dropIfExists('users');
        Capsule::schema()->dropIfExists('study_groups');
    }
}