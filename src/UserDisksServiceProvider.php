<?php

namespace Biigle\Modules\UserDisks;

use Biigle\Http\Requests\UpdateUserSettings;
use Biigle\Modules\AuthHaai\SocialiteProvider;
use Biigle\Modules\UserDisks\Console\Commands\CheckExpiredUserDisks;
use Biigle\Modules\UserDisks\Console\Commands\PruneExpiredUserDisks;
use Biigle\Modules\UserDisks\Console\Commands\RefreshDCacheTokens;
use Biigle\Modules\UserStorage\UserStorageServiceProvider;
use Biigle\Services\Modules;
use Biigle\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

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
                'filesManual',
            ],
            'controllerMixins' => [
                //
            ],
            'apidoc' => [
               __DIR__.'/Http/Controllers/Api/',
            ],
        ]);

        // The user storage module has precedence. Only add this if the module is not
        // installed.
        if (!class_exists(UserStorageServiceProvider::class)) {
            $modules->registerViewMixin('user-disks', 'navbarMenuItem');
        }

        $this->publishes([
            __DIR__.'/public' => public_path('vendor/user-disks'),
        ], 'public');

        $this->addStorageConfigResolver();
        $this->overrideUseDiskGateAbility();

        if (config('user_disks.notifications.allow_user_settings')) {
            $modules->registerViewMixin('user-disks', 'settings.notifications');
            UpdateUserSettings::addRule('storage_disk_notifications', 'filled|in:email,web');
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                CheckExpiredUserDisks::class,
                PruneExpiredUserDisks::class,
                RefreshDCacheTokens::class,
            ]);

            $this->app->booted(function () {
                $schedule = app(Schedule::class);
                $schedule->command(CheckExpiredUserDisks::class)
                    ->daily()
                    ->onOneServer();

                $schedule->command(PruneExpiredUserDisks::class)
                    ->daily()
                    ->onOneServer();

                $schedule->command(RefreshDCacheTokens::class)
                    ->hourly()
                    ->onOneServer();
            });
        }
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
                    // Disks are automatically extended each time they are used.
                    $disk->extend();

                    // Refresh dcache token if it's about to expire.
                    if ($disk->isDCacheAccessTokenExpiring()) {
                        $disk->refreshDCacheToken();
                    }

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
        // Only extend the use-disk gate (if it exists) because other modules may use
        // it too.
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
