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
        $this->assertNotNull($this->model->options);
        $this->assertNotNull($this->model->user);
        $this->assertNotNull($this->model->created_at);
        $this->assertNotNull($this->model->updated_at);
    }

    public function testEncryptOptions()
    {
        $options = [
            'id' => 'abcde',
            'secret' => 'fghij',
        ];
        $this->model->options = $options;
        $attributes = $this->model->getAttributes();
        $this->assertEquals($options, json_decode(Crypt::decryptString($attributes['options']), true));
    }

    public function testGetConfigTemplate()
    {
        $template = [
            'driver' => 'local',
            'key' => 'value',
        ];
        config(['user_disks.templates.test' => $template]);

        $this->assertEquals($template, UserDisk::getConfigTemplate('test'));
    }

    public function testGetConfig()
    {
        $template = [
            'driver' => 'local',
            'key' => 'value',
        ];
        config(['user_disks.templates.test' => $template]);

        $disk = UserDisk::factory()->make([
            'type' => 'test',
            'options' => [
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
            'options' => [
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

    public function testGetPublicOptionsAttributeS3()
    {
        $disk = UserDisk::factory()->make([
            'type' => 's3',
            'options' => [
                'key' => 'abc',
                'secret' => 'efg',
                'region' => 'us-east-1',
                'bucket' => 'BiigleTest',
                'endpoint' => 'https://s3.example.com',
                'use_path_style_endpoint' => true,
            ],
        ]);

        $expect = [
            'region' => 'us-east-1',
            'bucket' => 'BiigleTest',
            'endpoint' => 'https://s3.example.com',
            'use_path_style_endpoint' => true,
        ];

        $this->assertEquals($expect, $disk->public_options);
    }

    public function testGetStoreValidationRules()
    {
        $rules = [
            'secret' => 'required',
            'key' => 'required',
        ];
        config(['user_disks.store_validation.test' => $rules]);

        $this->assertEquals($rules, UserDisk::getStoreValidationRules('test'));
    }

    public function testGetStoreValidationRulesS3()
    {
        $rules = [
            'key' => 'required',
            'secret' => 'required',
            'region' => 'required',
            'bucket' => 'required',
            'endpoint' => 'required|url',
            'use_path_style_endpoint' => 'boolean',
        ];

        $this->assertEquals($rules, UserDisk::getStoreValidationRules('s3'));
    }

    public function testGetUpdateValidationRules()
    {
        $rules = [
            'secret' => 'required',
            'key' => 'required',
        ];
        config(['user_disks.update_validation.test' => $rules]);

        $this->assertEquals($rules, UserDisk::getUpdateValidationRules('test'));
    }

    public function testGetUpdateValidationRulesS3()
    {
        $rules = [
            'key' => 'filled',
            'secret' => 'filled',
            'region' => 'filled',
            'bucket' => 'filled',
            'endpoint' => 'filled|url',
            'use_path_style_endpoint' => 'boolean',
        ];

        $this->assertEquals($rules, UserDisk::getUpdateValidationRules('s3'));
    }
}
