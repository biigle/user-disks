<?php

namespace Biigle\Modules\UserDisks\Database\Factories;

use Biigle\Modules\UserDisks\UserDisk;
use Biigle\Modules\UserDisks\UserDiskType;
use Biigle\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserDiskFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = UserDisk::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'credentials' => [],
            'name' => $this->faker->name(),
            'type_id' => UserDiskType::factory(),
            'user_id' => User::factory(),
        ];
    }
}
