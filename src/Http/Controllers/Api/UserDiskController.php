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

class UserDiskController extends Controller
{
    /**
     * Initialize a new user storage disk
     *
     * @api {post} user-disks Create a new storage disk
     * @apiGroup StorageDisks
     * @apiName StoreStorageDisk
     * @apiPermission editor
     * @apiDescription Depending on the storage disk type, different additional arguments are required. Not all storage disk types listed here may be enabled for your BIIGLE instance.
     *
     * @apiParam (Required arguments) {String} type The storage disk type. One of `s3`, `webdav`, 'elements' or 'aruna'.
     * @apiParam (Required arguments) {String} name The name of the storage disk.
     *
     * @apiParam (S3 required arguments) {String} key The S3 access key.
     * @apiParam (S3 required arguments) {String} secret The S3 secret key.
     * @apiParam (S3 required arguments) {String} bucket The S3 bucket name.
     * @apiParam (S3 required arguments) {String} region The S3 region. Example: `us-east-1`.
     * @apiParam (S3 required arguments) {String} endpoint The S3 endpoint URL. Example `https://s3.example.com`.
     *
     * @apiParam (WebDAV required arguments) {String} baseUri The base URI of the WebDAV server.
     *
     * @apiParam (WebDAV optional arguments) {String} userName User name for authentication. Required if a password is given.
     * @apiParam (WebDAV optional arguments) {String} password Password for authentication. Required if a user name is given.
     * @apiParam (WebDAV optional arguments) {String} pathPrefix Path prefix to use for all requests. If your baseUri contains a path, it is automatically used as pathPrefix.
     *
     * @apiParam (Elements required arguments) {String} baseUri The base URI of the Elements server.
     * @apiParam (Elements required arguments) {String} token The Elements API token.
     *
     * @apiParam (Aruna required arguments) {String} endpoint The Aruna data proxy endpoint URL.
     * @apiParam (Aruna required arguments) {String} bucket The Aruna project name.
     * @apiParam (Aruna required arguments) {String} key The Aruna S3 access key.
     * @apiParam (Aruna required arguments) {String} secret The Aruna S3 secret key.
     *
     * @param StoreUserDisk $request
     * @throws ValidationException if the disk configuration is invalid
     * @return \Illuminate\Http\Response
     */
    public function store(StoreUserDisk $request)
    {
        $disk = DB::transaction(function () use ($request) {
            $disk = UserDisk::create([
                'name' => $request->input('name'),
                'type' => $request->input('type'),
                'user_id' => $request->user()->id,
                'expires_at' => now()->addMonths(config('user_disks.expires_months')),
                'options' => $request->getDiskOptions(),
            ]);

            match ($disk->type) {
                's3' => $this->validateS3Config($disk),
                'aruna' => $this->validateS3Config($disk),
                default => $this->validateGenericConfig($disk),
            };

            return $disk;
        });

        if ($this->isAutomatedRequest()) {
            return $disk;
        }

        return $this->fuzzyRedirect('storage-disks')
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
     *
     *
     * @apiParam (WebDAV attributes that can be updated) {String} baseUri The base URI of the WebDAV server.
     * @apiParam (WebDAV attributes that can be updated) {String} userName User name for authentication. Required if a password is given.
     * @apiParam (WebDAV attributes that can be updated) {String} password Password for authentication. Required if a user name is given.
     * @apiParam (WebDAV attributes that can be updated) {String} pathPrefix Path prefix to use for all requests. If your baseUri contains a path, it is automatically used as pathPrefix.
     *
     * @apiParam (Elements attributes that can be updated) {String} baseUri The base URI of the Elements server.
     * @apiParam (Elements attributes that can be updated) {String} token The Elements API token.
     *
     * @apiParam (Aruna attributes that can be updated) {String} endpoint The Aruna data proxy endpoint URL.
     * @apiParam (Aruna attributes that can be updated) {String} bucket The Aruna project name.
     * @apiParam (Aruna attributes that can be updated) {String} key The Aruna S3 access key.
     * @apiParam (Aruna attributes that can be updated) {String} secret The Aruna S3 secret key.
     *
     * @param UpdateUserDisk $request
     * @throws ValidationException if the disk configuration is invalid
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateUserDisk $request)
    {
        DB::transaction(function () use ($request) {
            $request->disk->name = $request->input('name', $request->disk->name);
            $request->disk->options = array_merge(
                $request->disk->options,
                $request->getDiskOptions()
            );

            $request->disk->save();

            match ($request->disk->type) {
                's3' => $this->validateS3Config($request->disk),
                'aruna' => $this->validateS3Config($request->disk),
                default => $this->validateGenericConfig($request->disk),
            };
        });

        if (!$this->isAutomatedRequest()) {
            return $this->fuzzyRedirect()
                ->with('message', 'Storage disk updated')
                ->with('messageType', 'success');
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

    /**
     * Validates the given user s3 disk configuration
     *
     * @param UserDisk $disk The disk configuration to validate
     * @throws ValidationException If the disk configuration is invalid
     * @return void
     *
     */
    protected function validateS3Config(UserDisk $disk)
    {
        $options = $disk->options;
        $endpoint = $options['endpoint'];
        $bucket = $options['bucket'];

        // Check whether the endpoint contains the bucket name at the beginning or end of url
        if (!preg_match("/(\/\/{$bucket}\.|[a-zA-Z]\/{$bucket}($|\/))/", $endpoint)) {
            throw ValidationException::withMessages(['endpoint' => 'The endpoint URL must contain the bucket name. Please check if the name is present and spelled correctly.']);
        }

        try {
            $this->validateDiskAccess($disk);
        } catch (Exception $e) {
            $msg = $e->getMessage();

            if (Str::contains($msg, 'timeout', true)) {
                throw ValidationException::withMessages(['error' => 'The endpoint URL could not be accessed. Does it exist?']);
            } elseif (Str::contains($msg, ['cURL error', 'Error parsing XML'], true)) {
                throw ValidationException::withMessages(['endpoint' => 'This does not seem to be a valid S3 endpoint.']);
            } elseif (Str::contains($msg, ['AccessDenied', 'NoSuchBucket', 'NoSuchKey', 'InvalidAccessKeyId'], true)) {
                throw ValidationException::withMessages(['error' => 'The bucket could not be accessed. Please check for typos or missing access permissions.']);
            } else {
                throw ValidationException::withMessages(['error' => 'An error occurred. Please check if your input is correct.']);
            }
        }
    }

    /**
     * Validates the given user disk configuration
     *
     * @param UserDisk $disk The disk configuration to validate
     * @throws ValidationException If the disk configuration is invalid
     * @return void
     *
     */
    protected function validateGenericConfig(UserDisk $disk)
    {
        try {
            $this->validateDiskAccess($disk);
        } catch (Exception $e) {
            throw ValidationException::withMessages(['error' => 'The configuration seems to be invalid. ' . $e->getMessage()]);
        }
    }

    /**
     * Checks whether the endpoint URL is valid and the disk is accessible
     * 
     * @param mixed $disk The disk configured by the user that should be accessed
     * @return void
     * @throws Exception If the disk cannot be accessed
     */
    protected function validateDiskAccess($disk)
    {
        $disk = Storage::disk("disk-{$disk->id}");
        $files = $disk->getAdapter()->listContents('', false);
        // Need to access an element to verify whether the endpoint URL is valid
        $files->current();
    }
}
