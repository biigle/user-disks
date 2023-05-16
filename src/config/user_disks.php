<?php

return [
    /*
     | Each storage disk type (key must match the enum in the database) with it's full
     | name as value.
     */
    'types' => [
        's3' => 'S3',
    ],

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
            // Bucket and region are not needed because bucket_endpoint is true.
            // This means that the bucket and regions should be provided directly in the
            // endpoint URL instead.
            'region' => '',
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
