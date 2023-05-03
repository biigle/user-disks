<?php

namespace Biigle\Modules\UserDisks\Http\Controllers\Api;

use Biigle\Http\Controllers\Api\Controller;
use Biigle\Modules\UserDisks\Http\Requests\StoreUserDisk;
use Biigle\Modules\UserDisks\UserDisk;

class UserDiskController extends Controller
{
    /**
     * Initialize a new user disk
     *
     * @api {post} user-disks Create a new storage disk
     * @apiGroup UserDisks
     * @apiName StoreUserDisk
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
        $optionKeys = array_keys(UserDisk::getValidationRules($request->input('type')));

        return UserDisk::create([
            'name' => $request->input('name'),
            'type' => $request->input('type'),
            'user_id' => $request->user()->id,
            'options' => $request->safe()->only($optionKeys),
        ]);
    }
}
