<?php

namespace Biigle\Modules\UserDisks\Http\Controllers\Views;

use Biigle\Http\Controllers\Views\Controller;
use Biigle\Modules\UserDisks\UserDisk;
use Illuminate\Contracts\Auth\Guard;

class UserDiskController extends Controller
{
    /**
     * Shows the user disks settings.
     *
     * @param Guard $auth
     * @return \Illuminate\Http\Response
     */
    public function index(Guard $auth)
    {
        $this->authorize('create', UserDisk::class);

        $disks = UserDisk::where('user_id', $auth->user()->id)->get();

        return view('user-disks::index', [
            'disks' => $disks,
        ]);
    }
}
