<?php

namespace Biigle\Modules\UserDisks\Database\Factories;

use Biigle\Modules\UserDisks\UserDisk;
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
            'name' => $this->faker->name(),
            'options' => [],
            'type' => 's3',
            'user_id' => User::factory(),
            'expires_at' => now()->addMonth(),
        ];
    }
}
