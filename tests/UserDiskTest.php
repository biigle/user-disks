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
}
