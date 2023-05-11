<?php

namespace Biigle\Modules\UserDisks;

use Biigle\Modules\UserDisks\Database\Factories\UserDiskFactory;
use Biigle\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDisk extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'type',
        'user_id',
        'options',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'options' => 'encrypted:array',
        'expires_at' => 'datetime',
    ];

    /**
     * Return the storage disk config template associated with the disk type,
     *
     * @param string $type
     *
     * @return array
     */
    public static function getConfigTemplate($type)
    {
        return config("user_disks.templates.{$type}");
    }

    /**
     * Return the validation rules to create a disk with a specific type.
     *
     * @param string $type
     *
     * @return array
     */
    public static function getStoreValidationRules($type)
    {
        return config("user_disks.store_validation.{$type}");
    }

    /**
     * Return the validation rules to update a disk with a specific type.
     *
     * @param string $type
     *
     * @return array
     */
    public static function getUpdateValidationRules($type)
    {
        return config("user_disks.update_validation.{$type}");
    }

    /**
     * Check whether the disk is about to expire.
     *
     * @return boolean
     */
    public function isAboutToExpire()
    {
        return $this->expires_at < now()->addWeeks(config('user_disks.about_to_expire_weeks'));
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return UserDiskFactory::new();
    }

    /**
     * The user who owns the disk.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the filesystem disk configuration array of this user disk.
     *
     * @return array
     */
    public function getConfig()
    {
        return array_merge(static::getConfigTemplate($this->type), $this->options, [
            'read-only' => true,
        ]);
    }
}
