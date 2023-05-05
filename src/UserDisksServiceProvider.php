<?php

namespace Biigle\Modules\UserDisks;

use Biigle\Services\Modules;
use Illuminate\Routing\Router;
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
                'userSettingsMenu',
            ],
            'controllerMixins' => [
                //
            ],
            'apidoc' => [
               //__DIR__.'/Http/Controllers/Api/',
            ],
        ]);

        $this->publishes([
            __DIR__.'/public/assets' => public_path('vendor/user-disks'),
        ], 'public');

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
    * Register the service provider.
    *
    * @return  void
    */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/config/user_disks.php', 'user_disks');
    }
}
