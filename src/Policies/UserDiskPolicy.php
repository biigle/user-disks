<?php

namespace Biigle\Modules\UserDisks\Policies;

use Biigle\Modules\UserDisks\UserDisk;
use Biigle\Policies\CachedPolicy;
use Biigle\Role;
use Biigle\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserDiskPolicy extends CachedPolicy
{
    use HandlesAuthorization;

    /**
     * Intercept all checks.
     *
     * @param User $user
     * @param string $ability
     * @return bool|null
     */
    public function before($user, $ability)
    {
        $except = ['access', 'update'];

        if ($user->can('sudo') && !in_array($ability, $except)) {
            return true;
        }
    }

    /**
     * Determine if the given user can create a new disk.
     *
     * @param  User  $user
     * @return bool
     */
    public function create(User $user)
    {
        return $user->role_id === Role::editorId() || $user->role_id === Role::adminId();
    }

    /**
     * Determine if the given disk can be accessed (i.e. used for new volumes) by the user.
     *
     * @param  User  $user
     * @param  UserDisk  $disk
     * @return bool
     */
    public function access(User $user, UserDisk $disk)
    {
        return $user->id === $disk->user_id;
    }

    /**
     * Determine if the given user can update the storage disk.
     *
     * @param User $user
     * @param UserDisk $disk
     *
     * @return bool
     */
    public function update(User $user, UserDisk $disk)
    {
        return $this->access($user, $disk);
    }

    /**
     * Determine if the given user can destroy the storage disk.
     *
     * @param User $user
     * @param UserDisk $disk
     *
     * @return bool
     */
    public function destroy(User $user, UserDisk $disk)
    {
        return $this->access($user, $disk);
    }
}
