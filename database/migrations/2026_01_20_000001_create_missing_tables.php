<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Companies
        if (!Schema::hasTable('companies')) {
            Schema::create('companies', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->nullable();
                $table->string('logo')->nullable();
                $table->boolean('isActive')->default(true);
                $table->timestamps();
            });
        }

        // 2. Client Details (Ranges)
        if (!Schema::hasTable('client_details')) {
            Schema::create('client_details', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->index(); // No constraint to strictly avoid issues if created later, or add cascade if desired. Keeping simple.
                $table->string('name');
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->string('address')->nullable();
                $table->boolean('isActive')->default(true);
                $table->timestamps();
            });
        }

        // 3. Site Details (Beats)
        if (!Schema::hasTable('site_details')) {
            Schema::create('site_details', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->index();
                $table->foreignId('client_id')->index(); // References client_details.id
                $table->string('name');
                $table->string('client_name')->nullable(); // Denormalized for easier query
                $table->string('location')->nullable();
                $table->string('lat')->nullable();
                $table->string('lng')->nullable();
                $table->boolean('isActive')->default(true);
                $table->timestamps();
            });
        }

        // 4. Site Assign (User Assignments)
        if (!Schema::hasTable('site_assign')) {
            Schema::create('site_assign', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->index();
                $table->foreignId('user_id')->index();
                $table->foreignId('client_id')->nullable()->index();
                $table->text('site_id')->nullable(); // Can be CSV based on 'FIND_IN_SET' usage
                $table->string('client_name')->nullable();
                $table->string('site_name')->nullable();
                $table->date('date_assigned')->nullable();
                $table->timestamps();
            });
        }

        // 5. Patrol Sessions
        if (!Schema::hasTable('patrol_sessions')) {
            Schema::create('patrol_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->index();
                $table->foreignId('user_id')->index();
                $table->foreignId('site_id')->nullable()->index();
                $table->string('session')->nullable(); // e.g., 'Foot', 'Vehicle'
                $table->string('type')->nullable(); // e.g., 'Scheduled', 'Emergency'
                $table->dateTime('started_at')->nullable();
                $table->dateTime('ended_at')->nullable();
                $table->string('start_lat')->nullable();
                $table->string('start_lng')->nullable();
                $table->string('end_lat')->nullable();
                $table->string('end_lng')->nullable();
                $table->double('distance')->default(0);
                $table->longText('path_geojson')->nullable();
                $table->timestamps();
            });
        }

        // 6. Patrol Logs (Incidents/Events within a session)
        if (!Schema::hasTable('patrol_logs')) {
            Schema::create('patrol_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->index();
                $table->foreignId('patrol_session_id')->index();
                $table->string('type')->nullable(); // 'animal_sighting', 'animal_mortality', etc.
                $table->string('subtype')->nullable();
                $table->text('description')->nullable();
                $table->string('lat')->nullable();
                $table->string('lng')->nullable();
                $table->json('images')->nullable();
                $table->timestamps();
            });
        }

        // 7. Attendance
        if (!Schema::hasTable('attendance')) {
            Schema::create('attendance', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->index();
                $table->foreignId('user_id')->index();
                $table->foreignId('site_id')->nullable()->index();
                $table->date('dateFormat')->index(); // stored as date
                $table->time('check_in')->nullable();
                $table->time('check_out')->nullable();
                $table->boolean('attendance_flag')->default(0); // 1 = Present
                $table->integer('lateTime')->default(0); // Minutes late
                $table->string('start_lat')->nullable();
                $table->string('start_lng')->nullable();
                $table->timestamps();
            });
        }

        // 8. Incidence Details (Ad-hoc Incidents)
        if (!Schema::hasTable('incidence_details')) {
            Schema::create('incidence_details', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->index();
                $table->foreignId('site_id')->nullable()->index();
                $table->foreignId('guard_id')->nullable()->index(); // References users.id
                $table->date('dateFormat')->nullable();
                $table->string('type')->nullable();
                $table->integer('statusFlag')->default(0); // 0: Pending, 1: Resolved, etc.
                $table->text('description')->nullable();
                $table->string('lat')->nullable();
                $table->string('lng')->nullable();
                $table->json('images')->nullable();
                $table->timestamps();
            });
        }

        // 9. Site Geofences
        if (!Schema::hasTable('site_geofences')) {
            Schema::create('site_geofences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->index();
                $table->foreignId('site_id')->index();
                $table->string('name')->nullable();
                $table->string('type')->default('circle'); // circle, polygon
                $table->string('lat')->nullable();
                $table->string('lng')->nullable();
                $table->double('radius')->nullable();
                $table->text('poly_lat_lng')->nullable(); // For polygons
                $table->softDeletes();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_geofences');
        Schema::dropIfExists('incidence_details');
        Schema::dropIfExists('attendance');
        Schema::dropIfExists('patrol_logs');
        Schema::dropIfExists('patrol_sessions');
        Schema::dropIfExists('site_assign');
        Schema::dropIfExists('site_details');
        Schema::dropIfExists('client_details');
        Schema::dropIfExists('companies');
    }
};
