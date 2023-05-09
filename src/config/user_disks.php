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
            // These should be configured by the user.
            'key' => '',
            'secret' => '',
            'region' => '',
            'bucket' => '',
            'endpoint' => '',
            'use_path_style_endpoint' => false,
        ],
    ],

    /*
     | Validation rules for options when a new UserDisk is created. Each type has it's
     | own rules.
     */
    'store_validation' => [
        's3' => [
            'key' => 'required',
            'secret' => 'required',
            'region' => 'required',
            'bucket' => 'required',
            'endpoint' => 'required',
            'use_path_style_endpoint' => 'boolean',
        ],
    ],

    /*
     | Validation rules for options when a UserDisk is updated. Each type has it's
     | own rules.
     */
    'update_validation' => [
        's3' => [
            'key' => 'filled',
            'secret' => 'filled',
            'region' => 'filled',
            'bucket' => 'filled',
            'endpoint' => 'filled',
            'use_path_style_endpoint' => 'boolean',
        ],
    ],
];
