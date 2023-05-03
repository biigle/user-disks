<?php

return [
    /*
    | The default configuration arrays for filesystem disks that are filled differently
    | by each user disk. Each UserDiskType in the database must have an entry here with
    | the same key as the UserDiskType name.
    */
    'disk_templates' => [
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
     | Validation rules for credentials of each user disk type.
     */
    'disk_validation' => [
        's3' => [
            'key' => 'required',
            'secret' => 'required',
            'region' => 'required',
            'bucket' => 'required',
            'endpoint' => 'required|url',
            'use_path_style_endpoint' => 'boolean',
        ],
    ],
];
