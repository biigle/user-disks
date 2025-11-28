<?php

namespace Biigle\Modules\UserDisks\Http\Controllers\Api;

use Biigle\Http\Controllers\Api\Controller;
use Biigle\Modules\UserDisks\Http\Requests\ExtendUserDisk;
use Biigle\Modules\UserDisks\Http\Requests\StoreUserDisk;
use Biigle\Modules\UserDisks\Http\Requests\UpdateUserDisk;
use Biigle\Modules\UserDisks\UserDisk;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class UserDiskController extends Controller
{
    const DESY_TOKEN_ENDPOINT = "https://keycloak.desy.de/auth/realms/production/protocol/openid-connect/token";

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
        // TODO implement optional path prefix for dcache

        // TODO implement/update tests

        // TODO put dcache storage disk provider to separate package so
        // biigle/laravel-socialite-haai isnt installed everywhere?
        // Same with biigle/laravel-elements-storage?
        // Alternative: disable elements and dcache and add to readme that packages
        // have to be installed to enable the two.

        if ($request->input('type') === 'dcache') {
            $request->session()->put('dcache-disk-name', $request->input('name'));

            return Socialite::driver('haai')
                ->redirectUrl(url('/user-disks/dcache/callback'))
                // eduperson_principal_name is critical for the token exchange!
                ->setScopes(['openid', 'profile', 'email', 'eduperson_principal_name'])
                ->redirect();
        }

        return $this->validateAndCreateDisk($request->input('name'), $request->input('type'), $request->user()->id, $request->getDiskOptions());
    }

    /**
     * Handle the dCache authentication response.
     *
     * @api {get} user-disks/dcache/callback Handle the dCache authentication response
     * @apiGroup StorageDisks
     * @apiName dCacheAuthCallback
     * @apiPermission editor
     * @apiDescription This is the callback URL for the dCache OIDC authentication flow.
     */
    public function storeDCacheCallback(Request $request)
    {
        // TODO implement dcache temp url via macaroon in new filesystem adapter
        /*
            curl -E /tmp/x509up_u1000 -X POST -H 'Content-Type: application/macaroon-request' -d '{"caveats": ["activity:DOWNLOAD", "before:2019-09-25T08:12:11.080Z"]}' https://dcache.example.org/
         */

        try {
            $user = Socialite::driver('haai')
                ->redirectUrl(url('/user-disks/dcache/callback'))
                ->user();
        } catch (Exception $e) {
            throw $e; //TODO
        }

        $postData = [
            'client_id' => config('services.dcache-token-exchange.client_id'),
            'client_secret' => config('services.dcache-token-exchange.client_secret'),
            'grant_type' => 'urn:ietf:params:oauth:grant-type:token-exchange',
            'subject_token_type' => 'urn:ietf:params:oauth:token-type:access_token',
            'subject_token' => $user->token,
            'subject_issuer' => 'oidc',
            'audience' => 'token-exchange',
        ];

        try {
            $response = Http::asForm()->post(static::DESY_TOKEN_ENDPOINT, $postData);
        } catch (Exception $e) {
            throw $e;
            // TODO Handle error, not authorized to access dCache?
        }

        $data = $response->json();

        $name = $request->session()->pull('dcache-disk-name');

        // TODO implement scheduled job to refresh tokens
        // job runs every hour and refreshes all tokens with a refresh_token expiring within the next 2 hours
        // token refresh with a valid refresh_token has to be implemented in the storage disk resolver somehow (i.e. if a file is requested and the token is invalid but the refresh_token is valid, the token is automatically refreshed within the same request)
        // => Do this in UserDisk::extend()!


        $diskOptions = [
            'token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'],
            'token_expires_at' => now()->addSeconds($data['expires_in']),
            'refresh_token_expires_at' => now()->addSeconds($data['refresh_expires_in']),
        ];

        return $this->validateAndCreateDisk($name, 'dcache', $request->user()->id, $diskOptions);
    }

    /**
     * Check if a new storage disk has valid options/credentials and create it
     */
    protected function validateAndCreateDisk(string $name, string $type, int $userId, array $options = [])
    {
        $disk = DB::transaction(function () use ($name, $type, $userId, $options) {
            $disk = UserDisk::create([
                'name' => $name,
                'type' => $type,
                'user_id' => $userId,
                'expires_at' => now()->addMonths(config('user_disks.expires_months')),
                'options' => $options,
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
        // TODO: Update dcache token if refresh token is still valid otherwise get new
        // token

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
            throw ValidationException::withMessages(['error' => 'The configuration seems to be invalid.']);
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
