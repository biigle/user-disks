<?php

namespace Biigle\Modules\UserDisks\Http\Controllers\Api;

use Biigle\Http\Controllers\Api\Controller;
use Biigle\Modules\UserDisks\Http\Requests\ExtendUserDisk;
use Biigle\Modules\UserDisks\Http\Requests\StoreUserDisk;
use Biigle\Modules\UserDisks\Http\Requests\UpdateUserDisk;
use Biigle\Modules\UserDisks\UserDisk;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

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
        if ($request->input('type') === 'dcache') {
            $request->session()->put('dcache-disk-name', $request->input('name'));
            $request->session()->put('dcache-disk-pathPrefix', $request->input('pathPrefix'));

            return $this->dCacheAuthFlow();
        }

        return $this->validateAndCreateDisk(
            $request->input('name'),
            $request->input('type'),
            $request->user()->id,
            $request->getDiskOptions()
        );
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
    public function dCacheCallback(Request $request)
    {
        // This is null if a new disk is created.
        $id = $request->session()->pull('dcache-disk-id');

        if (is_null($id)) {
            $redirectResponse = redirect()->route('create-storage-disks');

            if (!$request->user()->can('create', UserDisk::class)) {
                return $redirectResponse
                    ->with('messageType', 'danger')
                    ->with('message', 'You are not authorized to create a storage disk.');
            }

        } else {
            $redirectResponse = redirect()->route('update-storage-disks', $id);
        }

        try {
            $user = Socialite::driver('haai')
                ->redirectUrl(url('/user-disks/dcache/callback'))
                ->user();
        } catch (Exception $e) {
            Log::error('There was an error while obtaining HAAI user attributes.', ['exception' => $e]);

            return $redirectResponse
                ->with('messageType', 'danger')
                ->with('message', 'There was an error while obtaining the user attributes.');
        }

        $postData = [
            'client_id' => config('user_disks.dcache-token-exchange.client_id'),
            'client_secret' => config('user_disks.dcache-token-exchange.client_secret'),
            'grant_type' => 'urn:ietf:params:oauth:grant-type:token-exchange',
            'subject_token_type' => 'urn:ietf:params:oauth:token-type:access_token',
            'subject_token' => $user->token,
            'subject_issuer' => 'oidc',
            'audience' => 'token-exchange',
            // Setting the scope here is critical, otherwise the scope will be reset
            // to the default scope after token refresh (and the token will no longer
            // work for dCache).
            'scope' => 'entitlements groups openid token-exchange profile email',
        ];

        try {
            $response = Http::asForm()->post(UserDisk::DCACHE_TOKEN_ENDPOINT, $postData);
        } catch (Exception $e) {
            Log::error('There was an error while obtaining a dCache token.', ['exception' => $e]);

            return $redirectResponse
                ->with('messageType', 'danger')
                ->with('message', 'There was an error while obtaining a dCache token.');
        }

        $data = $response->json();
        $diskOptions = [
            'token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'],
            'token_expires_at' => now()->addSeconds($data['expires_in']),
            'refresh_token_expires_at' => now()->addSeconds($data['refresh_expires_in']),
        ];

        // The auth flow was initiated for a new storage disk.
        if (is_null($id)) {
            $name = $request->session()->pull('dcache-disk-name');
            $pathPrefix = $request->session()->pull('dcache-disk-pathPrefix');

            if ($pathPrefix) {
                $diskOptions['pathPrefix'] = $pathPrefix;
            }

            return $this->validateAndCreateDisk(
                $name,
                'dcache',
                $request->user()->id,
                $diskOptions
            );
        }

        // Otherwise the auth flow was initiated for an existing storage disk.
        $disk = UserDisk::find($id);

        if (is_null($disk)) {
            return redirect()->route('create-storage-disks')
                ->with('messageType', 'danger')
                ->with('message', 'The storage disk could not be found.');
        } else if (!$request->user()->can('update', $disk)) {
            return redirect()->route('create-storage-disks')
                ->with('messageType', 'danger')
                ->with('message', 'You are not authorized to update this storage disk.');
        }

        $disk->options = array_merge($disk->options, $diskOptions);
        $disk->save();

        return $redirectResponse
            ->with('messageType', 'success')
            ->with('message', 'The dCache token was refreshed.');

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
        return DB::transaction(function () use ($request) {
            $request->disk->name = $request->input('name', $request->disk->name);
            $request->disk->options = array_merge(
                $request->disk->options,
                $request->getDiskOptions()
            );

            $request->disk->save();

            if ($request->disk->isDCacheRefreshTokenExpiring()) {
                $request->session()->put('dcache-disk-id', $request->disk->id);

                return $this->dCacheAuthFlow();
            }

            match ($request->disk->type) {
                's3' => $this->validateS3Config($request->disk),
                'aruna' => $this->validateS3Config($request->disk),
                default => $this->validateGenericConfig($request->disk),
            };

            if (!$this->isAutomatedRequest()) {
                return $this->fuzzyRedirect()
                    ->with('message', 'Storage disk updated')
                    ->with('messageType', 'success');
            }
        });
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
        if (Str::doesntStartWith($endpoint, "https://$bucket.") && Str::doesntEndWith($endpoint, "/$bucket")) {
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

    /**
     * Start the authentication flow for dCache.
     */
    protected function dCacheAuthFlow()
    {
        return Socialite::driver('haai')
            ->redirectUrl(url('/user-disks/dcache/callback'))
            // eduperson_principal_name is critical for the token exchange!
            ->setScopes(['openid', 'profile', 'email', 'eduperson_principal_name'])
            ->redirect();
    }
}
