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
        Schema::create('user_disks', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name', 128)->unique();

            // Each type must be present in the user_disks.disk_templates config, too.
            $table->enum('type', [
                's3',
                // 'aos',
            ]);

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // This stores the options (depending on type) as encrypted JSON.
            $table->text('options');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_disks');
    }
};
