<?php

namespace Biigle\Modules\UserDisks;

use Biigle\Modules\UserDisks\Database\Factories\UserDiskTypeFactory;
use Biigle\Traits\HasConstantInstances;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDiskType extends Model
{
    use HasConstantInstances, HasFactory;

    /**
     * The constant instances of this model.
     *
     * @var array
     */
    const INSTANCES = [
        's3' => 's3',
        'aos' => 'aos',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Return the storage disk config template associated with this disk type,
     *
     * @return array
     */
    public function getConfigTemplate()
    {
        return config("user_disks.disk_templates.{$this->name}");
    }

    /**
     * Return the storage disk validation rules associated with this disk type,
     *
     * @return array
     */
    public function getValidationRules()
    {
        return config("user_disks.disk_validation.{$this->name}");
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return UserDiskTypeFactory::new();
    }
}
