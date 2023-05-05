<?php

$router->group([
    'namespace' => 'Api',
    'prefix' => 'api/v1',
    'middleware' => ['api', 'auth:web,api'],
], function ($router) {
    $router->resource('user-disks', 'UserDiskController', [
        'only' => ['store', 'update', 'destroy'],
        'parameters' => ['user-disks' => 'id'],
    ]);
});

$router->group([
    'namespace' => 'Views',
    'middleware' => ['web', 'auth'],
], function ($router) {
    $router->get('settings/storage-disks', [
        'as' => 'settings-storage-disks',
        'uses' => 'UserDiskController@index',
    ]);
});
