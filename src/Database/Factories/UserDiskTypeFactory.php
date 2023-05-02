<?php

namespace Biigle\Modules\UserDisks\Database\Factories;

use Biigle\Modules\UserDisks\UserDiskType;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserDiskTypeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = UserDiskType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => 'test',
        ];
    }
}
