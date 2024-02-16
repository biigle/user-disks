<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_disks', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indices = $sm->listTableIndexes('user_disks');
            if (array_key_exists('user_disks_name_unique', $indices)) {
                $table->dropUnique('user_disks_name_unique');
                $table->unique(['name', 'user_id']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_disks', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indices = $sm->listTableIndexes('user_disks');
            if (array_key_exists('user_disks_name_user_id_unique', $indices)) {
                $table->dropUnique('user_disks_name_user_id_unique');
                $table->unique('name');
            }
            
        });
    }
};
