<?php

namespace Biigle\Modules\UserDisks\Http\Controllers\Views;

use Biigle\Http\Controllers\Views\Controller;
use Biigle\Modules\UserDisks\UserDisk;
use Illuminate\Http\Request;

class UserDiskController extends Controller
{
    /**
     * Shows the user disks settings.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('create', UserDisk::class);

        $disks = UserDisk::where('user_id', $request->user()->id)->get();

        return view('user-disks::index', [
            'disks' => $disks,
        ]);
    }

    /**
     * Shows the user disks create view.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', UserDisk::class);

        $chosenType = $request->input('type');
        $chosenName = $request->input('name');

        return view('user-disks::store', [
            'types' => config('user_disks.types'),
            'chosenType' => $chosenType,
            'chosenName' => $chosenName,
            'stepTwo' => $chosenType && $chosenName,
        ]);
    }

    /**
     * Shows the user disks create view.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update($id)
    {
        $disk = UserDisk::findOrFail($id);
        $this->authorize('update', $disk);

        return view('user-disks::update', [
            'disk' => $disk,
        ]);
    }
}
