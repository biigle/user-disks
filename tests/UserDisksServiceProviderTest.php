<?php

namespace Biigle\Tests\Modules\UserDisks;

use Biigle\Modules\UserDisks\UserDisksServiceProvider;
use TestCase;

class UserDisksServiceProviderTest extends TestCase
{
    public function testServiceProvider()
    {
        $this->assertTrue(class_exists(UserDisksServiceProvider::class));
    }
}
