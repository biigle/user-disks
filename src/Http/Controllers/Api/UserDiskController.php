<?php

namespace Biigle\Modules\UserDisks\Http\Controllers\Api;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Biigle\Modules\UserDisks\UserDisk;
use Illuminate\Support\Facades\Storage;
use Biigle\Http\Controllers\Api\Controller;
use Illuminate\Validation\ValidationException;
use Biigle\Modules\UserDisks\Http\Requests\StoreUserDisk;
use Biigle\Modules\UserDisks\Http\Requests\ExtendUserDisk;
use Biigle\Modules\UserDisks\Http\Requests\UpdateUserDisk;
use Biigle\Modules\UserDisks\Exceptions\DiskValidationException;

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
        try {
            $disk = DB::transaction(function () use ($request) {
                $disk = UserDisk::create([
                    'name' => $request->input('name'),
                    'type' => $request->input('type'),
                    'user_id' => $request->user()->id,
                    'expires_at' => now()->addMonths(config('user_disks.expires_months')),
                    'options' => $request->getDiskOptions(),
                ]);

                $this->validateS3Config($disk);

                return $disk;
            });

            if ($this->isAutomatedRequest()) {
                return $disk;
            }

            return $this->fuzzyRedirect('storage-disks')
                ->with('message', 'Storage disk created')
                ->with('messageType', 'success');
        } catch (DiskValidationException $e) {
            return $this->fuzzyRedirect()
                ->withErrors($e->getValidationError())
                ->withInput();
        }
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
        try {
            DB::transaction(function () use ($request) {
                $request->disk->name = $request->input('name', $request->disk->name);
                $request->disk->options = array_merge(
                    $request->disk->options,
                    $request->getDiskOptions()
                );

                $request->disk->save();
                $this->validateS3Config($request->disk);
            });

            if (!$this->isAutomatedRequest()) {
                return $this->fuzzyRedirect()
                    ->with('message', 'Storage disk updated')
                    ->with('messageType', 'success');
            }
        } catch (DiskValidationException $e) {
            return $this->fuzzyRedirect()
                ->withErrors($e->getValidationError())
                ->withInput();
        }
    }

    /**
     * Extend a storage disk
     *
     * @api {post} storage-disks/:id/extend Extend a storage disk
     * @apiGroup StorageDisks
     * @apiName ExtendStorageDisk
     * @apiPermission storageDiskOwner
     *
     * @apiParam {Number} id The storage disk ID.
     *
     * @param ExtendUserDisk $request
     * @return \Illuminate\Http\Response
     */
    public function extend(ExtendUserDisk $request)
    {
        $request->disk->extend();

        if (!$this->isAutomatedRequest()) {
            return $this->fuzzyRedirect()
                ->with('message', 'Storage disk extended')
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
            return $this->fuzzyRedirect('storage-disks')
                ->with('message', 'Storage disk deleted')
                ->with('messageType', 'success');
        }
    }
    protected function validateS3Config(UserDisk $disk)
    {
        if ($disk->type != 's3') {
            return;
        }

        $options = $disk->options;
        $endpoint = $options['endpoint'];
        $bucket = $options['bucket'];

        // Check if endpoint contains bucket name
        if (!preg_match("/(\b\/" . $bucket . "\.|\b" . $bucket . "\b)/", $endpoint)) {
            throw ValidationException::withMessages(['endpoint' => 'Endpoint url must contain bucket name. Please check if name is present and spelled correctly.']);
        }

        try {
            $this->canAccessDisk($disk);
        } catch (Exception $e) {
            $msg = $e->getMessage();

            if (Str::contains($msg, 'timeout', true)) {
                throw  ValidationException::withMessages(['error' => 'The endpoint URL could not be accessed. Does it exist?']);
            } elseif (Str::contains($msg, ['cURL error', 'Error parsing XML'], true)) {
                throw  ValidationException::withMessages(['endpoint' => 'This does not seem to be a valid S3 endpoint.']);
            } elseif (Str::contains($msg, ["AccessDenied", "NoSuchBucket", "NoSuchKey"], true)) {
                throw  ValidationException::withMessages(['error' => 'The bucket could not be accessed. Please check for typos or missing access permissions.']);
            } else {
                throw  ValidationException::withMessages(['error' => 'An error occurred. Please check if your input is correct.']);
            }
        }
        return;
    }

    protected function canAccessDisk($disk)
    {
        $disk = Storage::disk("disk-{$disk->id}");
        $files = $disk->getAdapter()->listContents('', false);
        // Need to access an element to check if endpoint url is valid
        $files->current();
    }
}
