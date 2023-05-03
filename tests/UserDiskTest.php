<?php

namespace Biigle\Tests\Modules\UserDisks;

use Biigle\Modules\UserDisks\UserDisk;
use Biigle\Modules\UserDisks\UserDiskType;
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

    public function testGetConfig()
    {
        $disk = UserDisk::factory()->make([
            'type_id' => UserDiskType::s3Id(),
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
        $this->expectException(\Exception::class);
        $this->model->getConfig();
    }
}
