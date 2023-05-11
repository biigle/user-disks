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

    $router->post('user-disks/{id}/extend', 'UserDiskController@extend');
});

$router->group([
    'namespace' => 'Views',
    'middleware' => ['web', 'auth'],
], function ($router) {
    $router->get('storage-disks', [
        'as' => 'storage-disks',
        'uses' => 'UserDiskController@index',
    ]);

    $router->get('storage-disks/create', [
        'as' => 'create-storage-disks',
        'uses' => 'UserDiskController@store',
    ]);

    $router->get('storage-disks/{id}', [
        'as' => 'update-storage-disks',
        'uses' => 'UserDiskController@update',
    ]);
});
