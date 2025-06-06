<?php

return [
    /*
     | Available types for new storage disks. Supported are: s3, webdav, elements, aruna.
     */
    'types' => array_filter(explode(',', env('USER_DISKS_TYPES', 's3'))),

    /*
    | The default configuration arrays for filesystem disks that are filled differently
    | by each user disk. Each UserDisk type enum in the database must have an entry here
    | withthe same key as the type.
    */
    'templates' => [
        's3' => [
            // These options are fixed.
            'driver' => 's3',
            'stream_reads' => true,
            'http' => [
                'connect_timeout' => 5,
            ],
            'throw' => true,
            'bucket_endpoint' => true,
            // The region may or may not be provided. However, the S3 SDK requires at
            // least a placeholder.
            'region' => 'us-east-1',
            // These should be configured by the user.
            'bucket' => '',
            'key' => '',
            'secret' => '',
            'endpoint' => '',
        ],

        'webdav' => [
            'driver' => 'webdav',
            'baseUri' => '',
            'userName' => '',
            'password' => '',
            'pathPrefix' => '',
        ],

        'elements' => [
            'driver' => 'elements',
            'baseUri' => '',
            'token' => '',
        ],

        // Basically the same than S3 above.
        'aruna' => [
            'driver' => 's3',
            'stream_reads' => true,
            'http' => [
                'connect_timeout' => 5,
            ],
            'throw' => true,
            'bucket_endpoint' => true,
            'use_path_style_endpoint' => false,
            'region' => 'us-east-1',
            // These should be configured by the user.
            'bucket' => '',
            'key' => '',
            'secret' => '',
            'endpoint' => '',
        ],
    ],

    /*
     | Validation rules for options when a new UserDisk is created. Each type has it's
     | own rules.
     */
    'store_validation' => [
        's3' => [
            'bucket' => 'required',
            'region' => 'nullable',
            'endpoint' => 'required|url',
            'key' => 'required',
            'secret' => 'required',
        ],

        'webdav' => [
            'baseUri' => 'required|url',
            'userName' => 'required_with:password',
            'password' => 'required_with:userName',
            'pathPrefix' => 'filled',
        ],

        'elements' => [
            'baseUri' => 'required|url',
            'token' => 'required',
        ],

        'aruna' => [
            'bucket' => 'required',
            'endpoint' => 'required|url',
            'key' => 'required',
            'secret' => 'required',
        ],
    ],

    /*
     | Validation rules for options when a UserDisk is updated. Each type has it's
     | own rules.
     */
    'update_validation' => [
        's3' => [
            'bucket' => 'filled',
            'region' => 'nullable',
            'endpoint' => 'filled|url',
            'key' => 'filled',
            'secret' => 'filled',
        ],

        'webdav' => [
            'baseUri' => 'filled|url',
            'userName' => 'required_with:password',
            'password' => 'required_with:userName',
            'pathPrefix' => 'filled',
        ],

        'elements' => [
            'baseUri' => 'filled|url',
            'token' => 'filled',
        ],

        'aruna' => [
            'bucket' => 'filled',
            'endpoint' => 'filled|url',
            'key' => 'filled',
            'secret' => 'filled',
        ],
    ],

    /*
     | Number of months until a UserDisk expires after the last access.
     */
    'expires_months' => env('USER_DISKS_EXPIRES_MONTHS', 6),

    /*
    | Number of weeks before expiration when a UserDisk is classified as "about
    | to expire".
    */
    'about_to_expire_weeks' => env('USER_DISKS_ABOUT_TO_EXPIRE_WEEKS', 4),

    /*
    | Number of weeks to wait after expiration before a UserDisk is actually
    | deleted.
    */
    'delete_grace_period_weeks' => env('USER_DISKS_DELETE_GRACE_PERIOD_WEEKS', 1),

    'notifications' => [
        /*
        | Set the way notifications for user disks are sent by default.
        |
        | Available are: "email", "web"
        */
        'default_settings' => 'email',

        /*
        | Choose whether users are allowed to change their notification settings.
        | If set to false the default settings will be used for all users.
        */
        'allow_user_settings' => true,
    ],
];
