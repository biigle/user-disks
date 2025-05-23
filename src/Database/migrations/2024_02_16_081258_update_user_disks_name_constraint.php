<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_disks', function (Blueprint $table) {
            $table->dropUnique('user_disks_name_unique');
            $table->unique(['name', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_disks', function (Blueprint $table) {
            $table->dropUnique('user_disks_name_user_id_unique');
            $table->unique('name');
        });
    }
};
