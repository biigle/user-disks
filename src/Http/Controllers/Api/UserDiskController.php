<?php

namespace Biigle\Modules\UserDisks\Http\Controllers\Api;

use Biigle\Http\Controllers\Api\Controller;
use Biigle\Modules\UserDisks\Http\Requests\StoreUserDisk;
use Biigle\Modules\UserDisks\Http\Requests\UpdateUserDisk;
use Biigle\Modules\UserDisks\UserDisk;

class UserDiskController extends Controller
{
    /**
     * Initialize a new user storage disk
     *
     * @api {post} user-disks Create a new storage disk
     * @apiGroup StorageDisks
     * @apiName StoreStorageDisk
     * @apiPermission editor
     * @apiDescription Depending on the storage disk type, different additional arguments are required.
     *
     * @apiParam (Required arguments) {String} type The storage disk type. One of `s3` or `aos`.
     * @apiParam (Required arguments) {String} name The name of the storage disk.
     *
     * @apiParam (S3 required arguments) {String} key The S3 access key.
     * @apiParam (S3 required arguments) {String} secret The S3 secret key.
     * @apiParam (S3 required arguments) {String} bucket The S3 bucket name.
     * @apiParam (S3 required arguments) {String} region The S3 region. Example: `us-east-1`.
     * @apiParam (S3 required arguments) {String} endpoint The S3 endpoint URL. Example `https://s3.example.com`.
     *
     * @apiParam (S3 optional arguments) {Boolean} use_path_style_endpoint Set to `true` to use the S3 "path style endpoint" (e.g. `https://s3.example.com/BUCKETNAME`) instead of the subdomain-style (e.g. `https://BUCKETNAME.s3.example.com`). Default: `false`.
     *
     * @param StoreUserDisk $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreUserDisk $request)
    {
        $disk = UserDisk::create([
            'name' => $request->input('name'),
            'type' => $request->input('type'),
            'user_id' => $request->user()->id,
            'options' => $request->getDiskOptions(),
        ]);

        if ($this->isAutomatedRequest()) {
            return $disk;
        }

        return $this->fuzzyRedirect('settings-storage-disks')
            ->with('message', 'Storage disk created')
            ->with('messageType', 'success');
    }

    /**
     * Update a user storage disk
     *
     * @api {put} user-disks/:id Update a storage disk
     * @apiGroup StorageDisks
     * @apiName UpdateStorageDisk
     * @apiPermission storageDiskOwner
     * @apiDescription Depending on the storage disk type, different attributes can be updated.
     *
     * @apiParam {Number} id The storage disk ID.
     *
     * @apiParam (Attributes that can be updated) {String} name The name of the storage disk.
     *
     * @apiParam (S3 attributes that can be updated) {String} key The S3 access key.
     * @apiParam (S3 attributes that can be updated) {String} secret The S3 secret key.
     * @apiParam (S3 attributes that can be updated) {String} bucket The S3 bucket name.
     * @apiParam (S3 attributes that can be updated) {String} region The S3 region. Example: `us-east-1`.
     * @apiParam (S3 attributes that can be updated) {String} endpoint The S3 endpoint URL. Example `https://s3.example.com`.
     * @apiParam (S3 attributes that can be updated) {Boolean} use_path_style_endpoint Set to `true` to use the S3 "path style endpoint" (e.g. `https://s3.example.com/BUCKETNAME`) instead of the subdomain-style (e.g. `https://BUCKETNAME.s3.example.com`).
     *
     * @param UpdateUserDisk $request
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateUserDisk $request)
    {
        $request->disk->name = $request->input('name', $request->disk->name);
        $request->disk->options = array_merge(
            $request->disk->options,
            $request->getDiskOptions()
        );

        $request->disk->save();

        if (!$this->isAutomatedRequest()) {
            return $this->fuzzyRedirect()
                ->with('message', 'Storage disk updated')
                ->with('messageType', 'success');
        }
    }

    /**
     * Delete a storage disk
     *
     * @api {delete} user-disks/:id Delete a storage disk
     * @apiGroup StorageDisks
     * @apiName DestroyStorageDisk
     * @apiPermission storageDiskOwner
     * @apiDescription Volumes that use the storage disk will not be deleted but they will not be functional either (e.g. the annotation tools won't work).
     *
     * @apiParam {Number} id The storage disk ID.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $disk = UserDisk::findOrFail($id);
        $this->authorize('destroy', $disk);
        $disk->delete();

        if (!$this->isAutomatedRequest()) {
            return $this->fuzzyRedirect('settings-storage-disks')
                ->with('message', 'Storage disk deleted')
                ->with('messageType', 'success');
        }
    }
}
