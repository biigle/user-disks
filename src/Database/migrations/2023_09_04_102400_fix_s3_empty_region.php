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
        UserDisk::where('type', 's3')
            ->eachById(function ($disk) {
                $options = $disk->options;
                if (array_key_exists('region', $options) && is_null($options['region'])) {
                    unset($options['region']);
                    $disk->options = $options;
                    $disk->save();
                }
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
