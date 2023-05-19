<?php

use Biigle\Modules\UserDisks\UserDisk;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_disks', function (Blueprint $table) {
            $table->string('type_tmp', 32)->nullable();
        });
        UserDisk::eachById(function ($disk) {
            $disk->type_tmp = $disk->type;
            $disk->save();
        });
        Schema::table('user_disks', function (Blueprint $table) {
            // Remove nullable.
            $table->string('type_tmp', 32)->change();
            $table->dropColumn('type');
        });
        Schema::table('user_disks', function (Blueprint $table) {
            $table->renameColumn('type_tmp', 'type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        UserDisk::where('type', 'aruna')->delete();
        Schema::table('user_disks', function (Blueprint $table) {
            $table->dropColumn('type');
        });
        Schema::table('user_disks', function (Blueprint $table) {
            $table->enum('type', ['s3'])->default('s3');
        });
    }
};
