<?php

return [
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
            'endpoint' => 'required|url',
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
            'endpoint' => 'filled|url',
            'use_path_style_endpoint' => 'boolean',
        ],
    ],

    /*
     | The options of each disk type that should never be shown.
     */
    'secret_options' => [
        's3' => [
            'key',
            'secret',
        ],
    ],
];
