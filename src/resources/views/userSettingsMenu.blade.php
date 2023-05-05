@can('create', \Biigle\Modules\UserDisks\UserDisk::class)
    <li role="presentation"@if(Request::is('settings/storage-disks')) class="active" @endif><a href="{{route('settings-storage-disks')}}">Storage Disks</a></li>
@endcan
