<?php

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
        Schema::create('user_disk_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 32)->unique();
        });

        Schema::create('user_disks', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name', 128)->unique();

            $table->foreignId('type_id')
                ->constrained('user_disk_types')
                ->restrictOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // This stores the credentials (depending on type) as encrypted JSON.
            $table->text('credentials');
        });

        DB::table('user_disk_types')->insert([
            ['name' => 's3'],
            // ['name' => 'aos'],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_disks');
        Schema::dropIfExists('user_disk_types');
    }
};
