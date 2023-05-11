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
            'read-only' => true,
        ];

        $this->assertEquals($expect, $disk->getConfig());
    }

    public function testGetConfigReadOnly()
    {
        $template = [
            'driver' => 'local',
            'read-only' => false,
        ];
        config(['user_disks.templates.test' => $template]);

        $disk = UserDisk::factory()->make([
            'type' => 'test',
            'options' => [
                'read-only' => false,
            ],
        ]);

        $expect = [
            'driver' => 'local',
            'read-only' => true,
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
            'read-only' => true,
        ];

        $this->assertEquals($expect, $disk->getConfig());
    }

    public function testGetConfigTemplateDoesNotExist()
    {
        $this->expectException(\TypeError::class);
        $this->model->type = 'test';
        $this->model->getConfig();
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
        $this->assertNotEmpty(UserDisk::getStoreValidationRules('s3'));
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
        $this->assertNotEmpty(UserDisk::getUpdateValidationRules('s3'));
    }

    public function testIsAboutToExpire()
    {
        config(['user_disks.about_to_expire_weeks' => 4]);
        $this->model->expires_at = now()->addWeeks(5);
        $this->assertFalse($this->model->isAboutToExpire());
        $this->model->expires_at = now()->addWeeks(3);
        $this->assertTrue($this->model->isAboutToExpire());
    }

    public function testExtend()
    {
        config(['user_disks.expires_months' => 2]);
        $time = $this->model->expires_at;
        $this->model->extend();
        $this->assertNotEquals($time->toDateTimeString(), $this->model->expires_at->toDateTimeString());
    }
}
