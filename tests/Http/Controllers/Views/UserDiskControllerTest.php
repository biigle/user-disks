<?php

namespace Biigle\Tests\Modules\UserDisks\Http\Controllers\Views;

use ApiTestCase;
use Biigle\Modules\UserDisks\UserDisk;

class UserDiskControllerTest extends ApiTestCase
{
    public function testIndex()
    {
        $this->get('settings/storage-disks')->assertStatus(302);

        $this->beGlobalGuest();
        $this->get('settings/storage-disks')->assertStatus(403);

        $this->beUser();
        $this->get('settings/storage-disks')->assertStatus(200);
    }
}
