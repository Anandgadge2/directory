<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create roles table
        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('description')->nullable();
                $table->timestamps();
            });

            // Insert default roles
            DB::table('roles')->insert([
                ['id' => 1, 'name' => 'SuperAdmin', 'description' => 'Can see everything', 'created_at' => now(), 'updated_at' => now()],
                ['id' => 2, 'name' => 'Supervisor', 'description' => 'Can see only guards under them', 'created_at' => now(), 'updated_at' => now()],
                ['id' => 3, 'name' => 'Guard/Employee', 'description' => 'No login access', 'created_at' => now(), 'updated_at' => now()],
                ['id' => 7, 'name' => 'Admin', 'description' => 'Can see clients, supervisors and guards', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        // Add supervisor_id to site_assign table
        if (!Schema::hasColumn('site_assign', 'supervisor_id')) {
            Schema::table('site_assign', function (Blueprint $table) {
                $table->foreignId('supervisor_id')->nullable()->after('user_id')->index();
            });
        }

        // Make phone unique in users table for login
        if (Schema::hasColumn('users', 'phone')) {
            Schema::table('users', function (Blueprint $table) {
                // First, check if the column is already unique
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexesFound = $sm->listTableIndexes('users');
                
                $phoneIsUnique = false;
                foreach ($indexesFound as $index) {
                    if ($index->isUnique() && in_array('phone', $index->getColumns())) {
                        $phoneIsUnique = true;
                        break;
                    }
                }

                if (!$phoneIsUnique) {
                    $table->string('phone')->unique()->change();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
        
        if (Schema::hasColumn('site_assign', 'supervisor_id')) {
            Schema::table('site_assign', function (Blueprint $table) {
                $table->dropColumn('supervisor_id');
            });
        }
    }
};
