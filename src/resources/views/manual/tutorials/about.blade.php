@extends('manual.base')

@section('manual-title', 'Storage disks')

@section('manual-content')
    <div class="row">
        <p class="lead">
            Storage disks serve volume files from a cloud storage service.
        </p>

        <p>
            Storage disks allow you to connect your BIIGLE account with cloud storage services. Files from your storage disks can be used to create new volumes in BIIGLE but remain under your control. Storage disks are different to <a href="{{route('manual-tutorials', ['volumes', 'remote-locations'])}}">remote locations</a> because the files require access credentials.
        </p>

        <p>
            To create a new storage disk, click on "Storage" in the dropdown menu of the navbar at the top. Next, click on the "Storage Disks" item in the navigation on the left (if it is not already selected). This will open a list of all your storage disks. The list shows the names and types (see below) of your storage disks, as well as the time when they were created.
        </p>

        <p>
            To create a new storage disk, click on "Add a new storage disk" below the list. This will open a form where you first have to choose the storage disk type and name. Then, click <button class="btn btn-success btn-xs">Continue</button> and enter the storage disk options which are different for each storage disk type. The options of each type are explained below. Finally, click <button class="btn btn-success btn-xs">Create disk</button> and the new storage disk will be created.
        </p>

        <p>
            To edit a storage disk, move the mouse over the item in the list of storage disks, then click on the <button class="btn btn-default btn-xs"><i class="fa fa-pen"></i></button> button. This will open a form where you can update the name and options of the storage disk. You can also delete the storage disk here. Volumes that use a storage disk will not be deleted when the storage disk is deleted. However, the volume files cannot be loaded any more and most volume features will no longer work.
        </p>

        <p>
            Storage disks will expire {{config('user_disks.expires_months')}} months after the last access. This is done to remove their sensitive access credentials from the BIIGLE database if they are no longer used. You will receive a notification if one of your storage requests is about to expire. Storage disks that are about to expire can be manually extended by clicking on the <button class="btn btn-default btn-xs"><i class="fa fa-redo"></i></button> button of the item in the list of your storage disks.
        </p>

        <h3>Storage disk types</h3>

        <p>
            Different types of storage disks are required to connect with different cloud storage services.
        </p>

        <div class="panel panel-warning">
            <div class="panel-body text-warning">
                Most storage disk types require you to enter access credentials. These access credentials are stored encrypted in the BIIGLE database. While BIIGLE is configured to only have read access to the files, you should lock down the access credentials as much as possible to prevent unauthorized use.
            </div>
        </div>

        <p>
            These storage disk types are available:
        </p>

        <ul>
            @if(in_array('s3', config('user_disks.types')))
                <li>
                    <a href="#s3">S3</a>
                </li>
            @endif
            @if(in_array('webdav', config('user_disks.types')))
                <li>
                    <a href="#webdav">WebDAV</a>
                </li>
            @endif
            @if(in_array('elements', config('user_disks.types')))
                <li>
                    <a href="#elements">Elements</a>
                </li>
            @endif
            @if(in_array('s3', config('user_disks.types')))
                <li>
                    <a href="#aos">Aruna Object Storage</a>
                </li>
            @endif
            @if(empty(config('user_disks.types')))
                <li class="text-muted">
                    No types are available. Please ask your administrator for help.
                </li>
            @endif
        </ul>

        @if(in_array('s3', config('user_disks.types')))
            @include("user-disks::manual.types.s3")
        @endif

        @if(in_array('webdav', config('user_disks.types')))
            @include("user-disks::manual.types.webdav")
        @endif

        @if(in_array('elements', config('user_disks.types')))
            @include("user-disks::manual.types.elements")
        @endif

        @if(in_array('s3', config('user_disks.types')))
            @include("user-disks::manual.types.aos")
        @endif
    </div>
@endsection
