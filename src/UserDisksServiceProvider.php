<?php

namespace Biigle\Modules\UserDisks;

use Biigle\Modules\UserStorage\UserStorageServiceProvider;
use Biigle\Services\Modules;
use Biigle\User;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Storage;

class UserDisksServiceProvider extends ServiceProvider
{

   /**
   * Bootstrap the application events.
   *
   * @param Modules $modules
   * @param  Router  $router
   * @return  void
   */
    public function boot(Modules $modules, Router $router)
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', 'user-disks');
        $this->loadMigrationsFrom(__DIR__.'/Database/migrations');

        $router->group([
            'namespace' => 'Biigle\Modules\UserDisks\Http\Controllers',
            'middleware' => 'web',
        ], function ($router) {
            require __DIR__.'/Http/routes.php';
        });

        $modules->register('user-disks', [
            'viewMixins' => [
                'storageMenu',
            ],
            'controllerMixins' => [
                //
            ],
            'apidoc' => [
               //__DIR__.'/Http/Controllers/Api/',
            ],
        ]);

        // The user storage module has precedence. Only add this if the module is not
        // installed.
        if (!class_exists(UserStorageServiceProvider::class)) {
            $modules->registerViewMixin('user-disks', 'navbarMenuItem');
        }

        $this->publishes([
            __DIR__.'/public/assets' => public_path('vendor/user-disks'),
        ], 'public');

        $this->addStorageConfigResolver();
        $this->overrideUseDiskGateAbility();
    }

    /**
    * Register the service provider.
    *
    * @return  void
    */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/config/user_disks.php', 'user_disks');
    }

    /**
     * Add the storage disk config resolver for 'disk-*' disks.
     */
    protected function addStorageConfigResolver()
    {
        // This is used to resolve dynamic "disk-xxx" storage disks.
        Storage::addConfigResolver(function ($name) {
            $matches = [];
            if (preg_match('/^disk-([0-9]+)$/', $name, $matches) === 1) {
                $disk = UserDisk::find($matches[1]);

                if ($disk) {
                    return $disk->getConfig();
                }
            }

            return null;
        });
    }

    /**
     * Add user disks logic to the 'use-disk' ability.
     */
    protected function overrideUseDiskGateAbility()
    {
        // Override gate to allow own user disk.
        $abilities = Gate::abilities();
        $useDiskAbility = $abilities['use-disk'] ?? fn () => false;
        Gate::define('use-disk', function (User $user, $disk) use ($useDiskAbility) {
            $matches = [];
            if (preg_match('/^disk-([0-9]+)$/', $disk, $matches)) {
                return $user->can('sudo') || UserDisk::where('user_id', $user->id)->where('id', $matches[1])->exists();
            }

            return $useDiskAbility($user, $disk);
        });
    }
}
