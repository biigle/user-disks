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
