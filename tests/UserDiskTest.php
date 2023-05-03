<?php

namespace Biigle\Tests\Modules\UserDisks;

use Biigle\Modules\UserDisks\UserDisk;
use Illuminate\Support\Facades\Crypt;
use ModelTestCase;

class UserDiskTest extends ModelTestCase
{
    /**
     * The model class this class will test.
     */
    protected static $modelClass = UserDisk::class;

    public function testAttributes()
    {
        $this->assertNotNull($this->model->type);
        $this->assertNotNull($this->model->name);
        $this->assertNotNull($this->model->credentials);
        $this->assertNotNull($this->model->user);
        $this->assertNotNull($this->model->created_at);
        $this->assertNotNull($this->model->updated_at);
    }

    public function testEncryptCredentials()
    {
        $credentials = [
            'id' => 'abcde',
            'secret' => 'fghij',
        ];
        $this->model->credentials = $credentials;
        $attributes = $this->model->getAttributes();
        $this->assertEquals($credentials, json_decode(Crypt::decryptString($attributes['credentials']), true));
    }

    public function testGetConfigTemplate()
    {
        $template = [
            'driver' => 'local',
            'key' => 'value',
        ];
        config(['user_disks.disk_templates.test' => $template]);

        $this->assertEquals($template, UserDisk::getConfigTemplate('test'));
    }

    public function testGetValidationRules()
    {
        $rules = [
            'driver' => 'required',
            'key' => 'filled',
        ];
        config(['user_disks.disk_validation.test' => $rules]);

        $this->assertEquals($rules, UserDisk::getValidationRules('test'));
    }

    public function testGetConfig()
    {
        $template = [
            'driver' => 'local',
            'key' => 'value',
        ];
        config(['user_disks.disk_templates.test' => $template]);

        $disk = UserDisk::factory()->make([
            'type' => 'test',
            'credentials' => [
                'key' => 'abc',
            ],
        ]);

        $expect = [
            'driver' => 'local',
            'key' => 'abc',
        ];

        $this->assertEquals($expect, $disk->getConfig());
    }

    public function testGetS3Config()
    {
        $disk = UserDisk::factory()->make([
            'type' => 's3',
            'credentials' => [
                'key' => 'abc',
                'secret' => 'efg',
                'region' => 'us-east-1',
                'bucket' => 'BiigleTest',
                'endpoint' => 'https://s3.example.com',
                'use_path_style_endpoint' => true,
            ],
        ]);

        $expect = [
            'driver' => 's3',
            'key' => 'abc',
            'secret' => 'efg',
            'region' => 'us-east-1',
            'bucket' => 'BiigleTest',
            'endpoint' => 'https://s3.example.com',
            'use_path_style_endpoint' => true,
            'stream_reads' => true,
            'http' => [
                'connect_timeout' => 5,
            ],
            'throw' => true,
        ];

        $this->assertEquals($expect, $disk->getConfig());
    }

    public function testGetConfigTemplateDoesNotExist()
    {
        $this->expectException(\TypeError::class);
        $this->model->type = 'test';
        $this->model->getConfig();
    }
}
